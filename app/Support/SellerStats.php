<?php

namespace App\Support;

use App\Models\Listing;
use App\Models\Seller;

/**
 * The numbers on a seller's dashboard and storefront page.
 *
 * One source, so the sidebar, the dashboard tiles and the public page
 * cannot disagree about how many listings a seller has. Counted from
 * `listings` rather than the denormalised `sellers.listings_count`, which
 * is demo data and goes stale the moment a seller publishes.
 */
final readonly class SellerStats
{
    private function __construct(
        public int $live,
        public int $drafts,
        public int $activeLots,
        public int $ordersToShip,
        public int $total,
    ) {}

    public static function for(?Seller $seller): self
    {
        if ($seller === null) {
            return new self(0, 0, 0, 0, 0);
        }

        $base = fn () => Listing::query()->where('seller_id', $seller->id);

        $live = (clone $base())->published();

        return new self(
            live: (clone $live)->count(),
            drafts: $base()->where('status', Listing::STATUS_DRAFT)->count(),
            activeLots: (clone $live)
                ->where('listing_type', Listing::TYPE_AUCTION)
                ->where('auction_ends_at', '>', now())
                ->count(),
            // No orders table yet; the tile exists so the shape is visible.
            ordersToShip: 0,
            total: $base()->count(),
        );
    }
}
