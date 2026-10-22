<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Today's USD conversion rates.
 *
 * The only place that talks to the rate provider, so every price on the
 * site converts with the same numbers fetched at the same moment.
 *
 * Three rules hold this together:
 *
 * - **A rate always carries its date.** A conversion shown without one
 *   can pass as today's when it is not, and a visitor comparing two
 *   stones would be comparing against different days.
 * - **A failure is never fatal.** The last good set is kept separately
 *   from the cache entry and used if the provider is down; past
 *   `max_age_days` even that is dropped and prices print in USD alone.
 *   A missing courtesy conversion is a far smaller problem than a wrong
 *   one.
 * - **Nothing here charges anyone.** The site transacts in USD; this
 *   only decides what is printed in brackets.
 */
final class ExchangeRates
{
    private const CACHE_KEY = 'verum:rates:current';

    /** Survives the TTL above, so a provider outage still has numbers. */
    private const FALLBACK_KEY = 'verum:rates:last-good';

    /**
     * Where the request's rates are held.
     *
     * On the request object, like DisplayCurrency: a container instance
     * or a static outlives the request, so one request that found no
     * rates would pin that answer for every request after it.
     */
    private const MEMO_KEY = 'verum.rates.memo';

    /**
     * Rate for one currency against USD, or null if it cannot be shown.
     *
     * Null is a normal answer: an unknown currency, a provider that has
     * never answered, or rates too stale to trust.
     */
    public static function for(string $currency): ?float
    {
        $currency = strtoupper($currency);

        if ($currency === self::base()) {
            return 1.0;
        }

        $rate = self::current()['rates'][$currency] ?? null;

        return is_numeric($rate) && $rate > 0 ? (float) $rate : null;
    }

    /** When the rates on hand were published, for the "as of" label. */
    public static function fetchedAt(): ?Carbon
    {
        $at = self::current()['fetched_at'];

        // Blank is the "no usable rates" marker set by current().
        if ($at === '') {
            return null;
        }

        try {
            return Carbon::parse($at);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Fetch and cache, ignoring the current cache entry.
     *
     * Called by the scheduled command so a visitor never waits on the
     * provider — a request that finds the cache warm never makes an
     * outbound call at all.
     *
     * @return array<string, float> the rates fetched, empty on failure
     */
    public static function refresh(): array
    {
        $fetched = self::fetch();

        if ($fetched === null) {
            return [];
        }

        self::remember($fetched);

        return $fetched['rates'];
    }

    public static function base(): string
    {
        return strtoupper((string) config('verum_currency.base', 'USD'));
    }

    /**
     * Drops the per-request memo, so the next read consults the cache again.
     *
     * Deliberately leaves the cached rates alone: this is for picking up
     * a new set, not for discarding one. `forget()` clears the cache too.
     */
    public static function flush(): void
    {
        request()->attributes->remove(self::MEMO_KEY);
    }

    /** Drops the memo *and* the cached rates. */
    public static function forget(): void
    {
        self::flush();
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::FALLBACK_KEY);
    }

    /**
     * The rates to use for this request.
     *
     * Memoised: a shop page prints dozens of prices and must not hit the
     * cache driver for each one.
     *
     * @return array{rates: array<string, float>, fetched_at: string}
     */
    private static function current(): array
    {
        $request = request();

        if ($request->attributes->has(self::MEMO_KEY)) {
            /** @var array{rates: array<string, float>, fetched_at: string} */
            return $request->attributes->get(self::MEMO_KEY);
        }

        /** @var array{rates: array<string, float>, fetched_at: string}|null $cached */
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached === null) {
            $cached = self::fetch();

            if ($cached !== null) {
                self::remember($cached);
            }
        }

        $cached ??= self::lastGood();

        $usable = self::fresh($cached) && $cached !== null
            ? $cached
            : ['rates' => [], 'fetched_at' => ''];

        $request->attributes->set(self::MEMO_KEY, $usable);

        return $usable;
    }

    /** @return array{rates: array<string, float>, fetched_at: string}|null */
    private static function lastGood(): ?array
    {
        /** @var array{rates: array<string, float>, fetched_at: string}|null */
        return Cache::get(self::FALLBACK_KEY);
    }

    /**
     * Is this set recent enough to print?
     *
     * @param  array{rates: array<string, float>, fetched_at: string}|null  $set
     */
    private static function fresh(?array $set): bool
    {
        if ($set === null || $set['rates'] === []) {
            return false;
        }

        try {
            $age = Carbon::parse($set['fetched_at'])
                ->diffInDays(now(), absolute: true);
        } catch (Throwable) {
            return false;
        }

        return $age <= (int) config('verum_currency.rates.max_age_days', 7);
    }

    /** @param  array{rates: array<string, float>, fetched_at: string}  $set */
    private static function remember(array $set): void
    {
        Cache::put(self::CACHE_KEY, $set, now()->addHours(
            (int) config('verum_currency.rates.ttl_hours', 12),
        ));

        // No TTL: this is what an outage falls back to. Staleness is
        // judged by fetched_at, not by eviction.
        Cache::forever(self::FALLBACK_KEY, $set);

        self::flush();
    }

    /**
     * One call to the provider.
     *
     * @return array{rates: array<string, float>, fetched_at: string}|null
     */
    private static function fetch(): ?array
    {
        try {
            $response = Http::timeout((int) config('verum_currency.rates.timeout_seconds', 8))
                ->acceptJson()
                ->get((string) config('verum_currency.rates.endpoint'));

            if ($response->failed()) {
                Log::warning('Exchange rates: provider returned an error.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $payload = $response->json();

            if (! is_array($payload) || ! is_array($payload['rates'] ?? null)) {
                Log::warning('Exchange rates: unexpected payload shape.');

                return null;
            }

            // Keep only usable numbers, so one bad entry cannot make a
            // price read as zero.
            $rates = [];

            foreach ($payload['rates'] as $code => $rate) {
                if (is_string($code) && is_numeric($rate) && (float) $rate > 0) {
                    $rates[strtoupper($code)] = (float) $rate;
                }
            }

            if ($rates === []) {
                return null;
            }

            return [
                'rates' => $rates,
                // The provider's own publication time where it gives one:
                // that is the day the figures are true for, which is not
                // necessarily when we asked.
                'fetched_at' => self::publishedAt($payload)->toIso8601String(),
            ];
        } catch (Throwable $e) {
            // Never fatal: a page must render without a conversion.
            Log::warning('Exchange rates: fetch failed.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /** @param  array<string, mixed>  $payload */
    private static function publishedAt(array $payload): CarbonInterface
    {
        $stamp = $payload['time_last_update_utc'] ?? null;

        if (is_string($stamp)) {
            try {
                return Carbon::parse($stamp);
            } catch (Throwable) {
                // Fall through to now().
            }
        }

        return now();
    }
}
