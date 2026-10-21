<?php

namespace App\Support;

use App\Models\Bid;
use App\Models\Buyer;
use App\Models\Offer;
use App\Models\SupportCase;

/**
 * The numbers on a buyer's dashboard.
 *
 * One source, so the tiles and the pages behind them cannot disagree —
 * the same shape as SellerStats.
 */
final readonly class BuyerStats
{
    private function __construct(
        public int $activeBids,
        public int $leadingBids,
        public int $openOffers,
        public int $openCases,
        public int $awaitingReview,
    ) {}

    public static function for(?Buyer $buyer): self
    {
        if ($buyer === null) {
            return new self(0, 0, 0, 0, 0);
        }

        $bids = Bid::query()->where('buyer_id', $buyer->id);

        return new self(
            activeBids: (clone $bids)->whereHas(
                'listing',
                fn ($query) => $query->where('auction_ends_at', '>', now()),
            )->distinct('listing_id')->count('listing_id'),

            // Still the top bid on a lot that is still running.
            leadingBids: (clone $bids)->whereNull('outbid_at')->whereHas(
                'listing',
                fn ($query) => $query->where('auction_ends_at', '>', now()),
            )->count(),

            openOffers: Offer::query()->where('buyer_id', $buyer->id)->live()->count(),

            openCases: SupportCase::query()->where('buyer_id', $buyer->id)->pending()->count(),

            awaitingReview: ReviewEligibility::awaitingReview($buyer)->count(),
        );
    }
}
