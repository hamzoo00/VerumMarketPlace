<?php

namespace App\Support;

use App\Models\Listing;
use Carbon\CarbonInterface;

/**
 * The house rules for an auction lot.
 *
 * Pure decisions about a lot's state — no persistence. PlaceBid applies
 * these; views ask them what to display. Keeping them here means the rule
 * is written once and cannot drift between the two.
 */
final class AuctionRules
{
    /**
     * What the next bid must at least be.
     *
     * The first bid meets the start price; after that each bid must clear
     * the current bid by the increment.
     */
    public static function minimumBid(Listing $listing): int
    {
        $start = (int) ($listing->start_price_cents ?? 0);
        $current = $listing->current_bid_cents;

        if ($current === null) {
            return $start;
        }

        return $current + self::increment($listing);
    }

    public static function increment(Listing $listing): int
    {
        $increment = (int) ($listing->bid_increment_cents ?? 0);

        return $increment > 0
            ? $increment
            : (int) config('verum_auctions.increment.default_cents');
    }

    public static function isOpen(Listing $listing): bool
    {
        return $listing->isAuction()
            && $listing->status === Listing::STATUS_ACTIVE
            && $listing->auction_ends_at !== null
            && $listing->auction_ends_at->isFuture();
    }

    /** Whether a bid now would land inside the anti-sniping window. */
    public static function isInSnipeWindow(Listing $listing): bool
    {
        if (! self::isOpen($listing)) {
            return false;
        }

        $window = (int) config('verum_auctions.anti_snipe.window_seconds');

        return $listing->auction_ends_at->diffInSeconds(now(), absolute: true) <= $window;
    }

    /**
     * Whether a bid landing now should push the close out.
     *
     * Extensions are capped so a determined pair of bidders cannot keep a
     * lot open indefinitely.
     */
    public static function shouldExtend(Listing $listing): bool
    {
        if (! self::isInSnipeWindow($listing)) {
            return false;
        }

        return $listing->extensions_count < (int) config('verum_auctions.anti_snipe.max_extensions');
    }

    /** The app uses CarbonImmutable (see AppServiceProvider), so return the interface. */
    public static function extendedEndsAt(Listing $listing): CarbonInterface
    {
        $seconds = (int) config('verum_auctions.anti_snipe.extend_seconds');

        return now()->addSeconds($seconds);
    }

    /** A hidden reserve: the lot does not sell below it. */
    public static function hasReserve(Listing $listing): bool
    {
        return $listing->reserve_price_cents !== null && $listing->reserve_price_cents > 0;
    }

    public static function reserveMet(Listing $listing): bool
    {
        if (! self::hasReserve($listing)) {
            return true;
        }

        return (int) ($listing->current_bid_cents ?? 0) >= (int) $listing->reserve_price_cents;
    }

    /** Buyers see whether a reserve exists and is met, never the figure. */
    public static function reserveLabel(Listing $listing): ?string
    {
        if (! self::hasReserve($listing)) {
            return null;
        }

        return self::reserveMet($listing)
            ? __('Reserve met')
            : __('Reserve not met');
    }

    /** A seller may not bid on their own lot. */
    public static function canBid(Listing $listing, ?int $buyerId): bool
    {
        return self::isOpen($listing) && $buyerId !== null;
    }

    /** Whether the lot sold, once the clock has run out. */
    public static function didSell(Listing $listing): bool
    {
        return $listing->bids_count > 0 && self::reserveMet($listing);
    }
}
