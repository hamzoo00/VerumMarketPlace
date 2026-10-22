<?php

namespace App\Console\Commands;

use App\Actions\Orders\OrderFromWin;
use App\Models\Listing;
use Illuminate\Console\Command;
use Throwable;

/**
 * Settles auctions whose time has run out.
 *
 * Without this a lot simply lapses: the page says "ended", the winning
 * bidder is owed a stone, and nothing anywhere records a sale. The
 * hammer has to fall somewhere.
 *
 * Safe to run repeatedly — OrderFromWin is idempotent per lot and buyer,
 * and a lot that is already settled is skipped.
 */
class CloseAuctions extends Command
{
    protected $signature = 'auctions:close {--limit=100 : How many lots to settle in one run}';

    protected $description = 'Settle ended auctions into orders';

    public function handle(OrderFromWin $orderFromWin): int
    {
        $lots = Listing::query()
            ->where('listing_type', Listing::TYPE_AUCTION)
            ->where('status', Listing::STATUS_ACTIVE)
            ->whereNotNull('auction_ends_at')
            ->where('auction_ends_at', '<=', now())
            ->limit((int) $this->option('limit'))
            ->get();

        $sold = 0;
        $unsold = 0;

        foreach ($lots as $lot) {
            try {
                $order = $orderFromWin->forAuction($lot);
            } catch (Throwable $e) {
                // One bad lot must not stop the sweep.
                $this->error("{$lot->slug}: {$e->getMessage()}");

                continue;
            }

            if ($order === null) {
                // No bids, or the reserve was not met: the lot closes
                // without a sale rather than staying open for ever.
                $lot->update(['status' => Listing::STATUS_ENDED]);
                $unsold++;

                continue;
            }

            $sold++;
        }

        $this->info("Settled {$sold} sold, {$unsold} unsold.");

        return self::SUCCESS;
    }
}
