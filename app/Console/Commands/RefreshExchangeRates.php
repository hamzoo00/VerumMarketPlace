<?php

namespace App\Console\Commands;

use App\Support\ExchangeRates;
use Illuminate\Console\Command;

/**
 * Pulls today's conversion rates into the cache.
 *
 * Scheduled, so a visitor never waits on the provider: a request that
 * finds the cache warm makes no outbound call at all. If this never ran
 * the site would still work — the first request would fetch, and a
 * failure only costs the bracketed figure.
 */
class RefreshExchangeRates extends Command
{
    protected $signature = 'currency:refresh';

    protected $description = 'Fetch the latest USD exchange rates for display conversion';

    public function handle(): int
    {
        $rates = ExchangeRates::refresh();

        if ($rates === []) {
            // Not a failure worth alarming on: the last good set is kept
            // and the site falls back to USD-only if it ages out.
            $this->warn('Could not fetch rates; keeping the last known set.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Fetched %d rates, published %s.',
            count($rates),
            ExchangeRates::fetchedAt()?->toDayDateTimeString() ?? 'unknown',
        ));

        return self::SUCCESS;
    }
}
