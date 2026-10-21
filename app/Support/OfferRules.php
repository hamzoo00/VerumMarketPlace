<?php

namespace App\Support;

use App\Models\Listing;
use App\Models\Money;
use App\Models\Offer;
use Carbon\CarbonImmutable;

/**
 * What an offer may be, and what happens to it on arrival.
 *
 * Pure decisions, no persistence — the same shape as AuctionRules. The
 * seller panel, the buyer form and PlaceOffer all read these, so none of
 * them can disagree about whether an offer is allowed.
 */
final class OfferRules
{
    /** Offers only make sense on a fixed-price listing that is live. */
    public static function isOpen(Listing $listing): bool
    {
        return $listing->accepts_offers
            && $listing->listing_type === Listing::TYPE_FIXED
            && $listing->isPublished()
            && $listing->price_cents !== null;
    }

    /**
     * The least a buyer may offer.
     *
     * The seller's auto-decline figure is a real floor, not a silent
     * rejection: telling the buyer the number up front beats letting them
     * submit something that was never going to be considered. `verdictFor`
     * still declines below it, as a backstop for anything that gets past
     * the form.
     */
    public static function minimumOffer(Listing $listing): int
    {
        $asking = (int) ($listing->price_cents ?? 0);

        $floor = (int) round(
            $asking * config('verum_offers.offers.min_percent_of_price') / 100
        );

        return max($floor, (int) ($listing->auto_decline_cents ?? 0));
    }

    public static function expiresAt(): CarbonImmutable
    {
        return now()->addHours((int) config('verum_offers.offers.expires_after_hours'));
    }

    /**
     * What should happen to this amount the moment it arrives.
     *
     * A seller may set a price they will always take and one they will
     * never take; between the two, they decide for themselves.
     *
     * @return Offer::STATUS_*
     */
    public static function verdictFor(Listing $listing, int $amountCents): string
    {
        $accept = $listing->auto_accept_cents;

        if ($accept !== null && $amountCents >= (int) $accept) {
            return Offer::STATUS_ACCEPTED;
        }

        $decline = $listing->auto_decline_cents;

        if ($decline !== null && $amountCents < (int) $decline) {
            return Offer::STATUS_DECLINED;
        }

        return Offer::STATUS_PENDING;
    }

    /** A negotiation has to settle; endless counters help nobody. */
    public static function roundsExhausted(int $round): bool
    {
        return $round >= (int) config('verum_offers.offers.max_rounds');
    }

    /**
     * Why this offer cannot be made, or null when it may.
     *
     * Returned as a message rather than a boolean so the caller can say
     * what is wrong without re-deriving it.
     */
    public static function rejectionReason(Listing $listing, int $amountCents): ?string
    {
        if (! self::isOpen($listing)) {
            return __('This listing is not accepting offers.');
        }

        $asking = (int) $listing->price_cents;

        if ($amountCents >= $asking) {
            return __('That is the asking price or more — buy it now instead.');
        }

        $minimum = self::minimumOffer($listing);

        if ($amountCents < $minimum) {
            return __('The lowest offer this seller will consider is :amount.', [
                'amount' => (new Money($minimum, $listing->currency))->format(),
            ]);
        }

        return null;
    }
}
