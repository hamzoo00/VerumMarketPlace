<?php

namespace App\Support;

use App\Models\Buyer;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\SellerReview;
use Illuminate\Database\Eloquent\Collection;

/**
 * Who may review what.
 *
 * A rating is only a trust signal if the reviewer actually transacted,
 * so eligibility is derived from the record — a won auction or an
 * accepted offer — and never from anything the browser sends.
 *
 * Pure decisions, like AuctionRules and OfferRules.
 */
final class ReviewEligibility
{
    /** Did this buyer win this lot? */
    public static function wonAuction(Listing $listing, Buyer $buyer): bool
    {
        if (! $listing->isAuction() || AuctionRules::isOpen($listing)) {
            return false;
        }

        // The highest bid when the hammer fell. `bids` is append-only, so
        // the top row is the winner.
        $top = $listing->bids()->orderByDesc('amount_cents')->orderBy('id')->first();

        return $top !== null && (int) $top->buyer_id === (int) $buyer->id;
    }

    /** Did this buyer have an offer accepted on this listing? */
    public static function hadOfferAccepted(Listing $listing, Buyer $buyer): bool
    {
        return $listing->offers()
            ->where('buyer_id', $buyer->id)
            ->where('status', Offer::STATUS_ACCEPTED)
            ->exists();
    }

    public static function canReview(Listing $listing, Buyer $buyer): bool
    {
        if (self::hasReviewed($listing, $buyer)) {
            return false;
        }

        return self::wonAuction($listing, $buyer)
            || self::hadOfferAccepted($listing, $buyer);
    }

    public static function hasReviewed(Listing $listing, Buyer $buyer): bool
    {
        return SellerReview::query()
            ->where('buyer_id', $buyer->id)
            ->where('listing_id', $listing->id)
            ->exists();
    }

    /**
     * Everything this buyer may still review.
     *
     * Drives the "leave a review" prompts on their dashboard, so they do
     * not have to go hunting for the listing.
     *
     * @return Collection<int, Listing>
     */
    public static function awaitingReview(Buyer $buyer): Collection
    {
        /** @var Collection<int, Listing> $candidates */
        $candidates = Listing::query()
            ->with('seller')
            ->where(fn ($query) => $query
                ->whereHas('bids', fn ($inner) => $inner->where('buyer_id', $buyer->id))
                ->orWhereHas('offers', fn ($inner) => $inner
                    ->where('buyer_id', $buyer->id)
                    ->where('status', Offer::STATUS_ACCEPTED)))
            ->whereDoesntHave('reviews', fn ($inner) => $inner->where('buyer_id', $buyer->id))
            ->get();

        // Winning is decided per lot, so the final filter happens here
        // rather than in SQL.
        return $candidates->filter(
            fn (Listing $listing): bool => self::canReview($listing, $buyer)
        )->values();
    }
}
