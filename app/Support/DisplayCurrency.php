<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * The country a visitor picked, and what to quote them in.
 *
 * Resolved once per request and held here, so a shop page printing fifty
 * prices reads the cookie once rather than fifty times. Money asks this
 * what to put in brackets; nothing else needs to know.
 *
 * The choice is a cookie, not a session or an account setting: it is a
 * display preference that a signed-out visitor must be able to make, and
 * it must survive them closing the tab.
 */
final class DisplayCurrency
{
    /**
     * Where the resolved choice is held for the rest of the request.
     *
     * Memoised on the request object itself, not in the container and not
     * in a static. Both of those outlive a single request — a container
     * instance persists for the app's lifetime, so the first request to
     * resolve "no country" would pin that answer for every request after
     * it under Octane, and for the rest of a test.
     *
     * The request is the thing whose lifetime actually matches the
     * question being asked, so the answer lives there.
     */
    private const ATTRIBUTE_KEY = 'verum.display-country';

    /**
     * The cookie name as a constant, for the encryption exemption.
     *
     * bootstrap/app.php builds middleware before config is available, so
     * it cannot call cookieName(); config may still rename the cookie, in
     * which case the exemption must be updated alongside it.
     */
    public const COOKIE_DEFAULT = 'verum_country';

    /** The chosen country, or null when the visitor has not picked one. */
    public static function country(): ?string
    {
        $request = request();

        if ($request->attributes->has(self::ATTRIBUTE_KEY)) {
            /** @var string|null */
            return $request->attributes->get(self::ATTRIBUTE_KEY);
        }

        /*
         * Read the cookie off the request, not through the Cookie facade.
         * That facade is the outgoing cookie *jar* and has no get() at
         * all, so asking it for an incoming cookie silently yields
         * nothing and every price would quietly stay in USD.
         */
        $raw = $request->cookie(self::cookieName());

        // A cookie can arrive as an array ("verum_country[]=x"), which is
        // not a country and must not be cast to one.
        $iso = is_string($raw) ? strtoupper($raw) : '';
        $resolved = self::supports($iso) ? $iso : null;

        $request->attributes->set(self::ATTRIBUTE_KEY, $resolved);

        return $resolved;
    }

    /**
     * The currency to show alongside USD, or null for none.
     *
     * Null whenever a second figure would be noise or a guess: no country
     * chosen, a country that quotes in USD anyway, or no usable rate.
     */
    public static function currency(): ?string
    {
        $iso = self::country();

        if ($iso === null) {
            return null;
        }

        $currency = self::currencyFor($iso);

        if ($currency === null || $currency === ExchangeRates::base()) {
            return null;
        }

        return ExchangeRates::for($currency) === null ? null : $currency;
    }

    /** When the rate behind the bracketed figure was published. */
    public static function ratesDate(): ?Carbon
    {
        return ExchangeRates::fetchedAt();
    }

    /** Is this a country we can quote a currency for? */
    public static function supports(?string $iso): bool
    {
        return $iso !== null
            && Countries::exists($iso)
            && self::currencyFor($iso) !== null;
    }

    public static function currencyFor(?string $iso): ?string
    {
        if ($iso === null) {
            return null;
        }

        /** @var array<string, string> $map */
        $map = config('verum_currency.currencies', []);

        return $map[strtoupper($iso)] ?? null;
    }

    public static function cookieName(): string
    {
        return (string) config('verum_currency.cookie', self::COOKIE_DEFAULT);
    }

    public static function cookieDays(): int
    {
        return (int) config('verum_currency.cookie_days', 365);
    }

    /**
     * Countries that can actually be quoted, for the picker.
     *
     * Built from the country list rather than the currency map so the
     * picker can never offer a country the rest of the site does not
     * know, and never one we have no rate for.
     *
     * @return array<string, array{name: string, currency: string}>
     */
    public static function options(): array
    {
        $options = [];

        foreach (Countries::all() as $iso => $country) {
            $currency = self::currencyFor($iso);

            if ($currency === null) {
                continue;
            }

            // A country whose currency has no rate today is still listed:
            // it quotes in USD, which is honest, and the list must not
            // change shape because a provider dropped one code.
            $options[$iso] = ['name' => $country['name'], 'currency' => $currency];
        }

        return $options;
    }

    /** Test seam: forget the resolved choice. */
    public static function flush(): void
    {
        request()->attributes->remove(self::ATTRIBUTE_KEY);
    }
}
