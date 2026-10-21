<?php

namespace App\Actions\Offers;

use App\Events\OfferChanged;
use App\Models\Buyer;
use App\Models\Listing;
use App\Models\Offer;
use App\Support\OfferRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A buyer opens a negotiation, or answers the seller's counter.
 *
 * The rules are re-checked here, not just in the form: a request can
 * arrive without ever touching the page, so this is the boundary that
 * actually decides what is allowed.
 */
class PlaceOffer
{
    public function handle(Listing $listing, Buyer $buyer, int $amountCents, ?string $message = null): Offer
    {
        $problem = OfferRules::rejectionReason($listing, $amountCents);

        if ($problem !== null) {
            throw ValidationException::withMessages(['amount' => $problem]);
        }

        $offer = DB::transaction(function () use ($listing, $buyer, $amountCents, $message): Offer {
            // Lock the thread: without it two tabs can both open a
            // negotiation on the same listing.
            $open = Offer::query()
                ->where('listing_id', $listing->id)
                ->where('buyer_id', $buyer->id)
                ->live()
                ->lockForUpdate()
                ->orderByDesc('round')
                ->first();

            if ($open !== null && $open->from === Offer::FROM_BUYER) {
                throw ValidationException::withMessages([
                    'amount' => __('You already have an offer waiting on this item.'),
                ]);
            }

            $round = $open === null ? 1 : $open->round + 1;

            if (OfferRules::roundsExhausted($round)) {
                throw ValidationException::withMessages([
                    'amount' => __('This negotiation has gone on long enough — buy it or let it go.'),
                ]);
            }

            // Answering a counter supersedes it rather than leaving two
            // live offers in one thread.
            $open?->update([
                'status' => Offer::STATUS_COUNTERED,
                'responded_at' => now(),
            ]);

            $offer = Offer::create([
                'listing_id' => $listing->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $listing->seller_id,
                'thread_id' => $open?->thread_id,
                'round' => $round,
                'from' => Offer::FROM_BUYER,
                'amount_cents' => $amountCents,
                'currency' => $listing->currency,
                'message' => $message,
                'status' => OfferRules::verdictFor($listing, $amountCents),
                'expires_at' => OfferRules::expiresAt(),
            ]);

            // The opening offer names its own thread.
            if ($offer->thread_id === null) {
                $offer->update(['thread_id' => $offer->id]);
            }

            // An auto-decision is answered the moment it is made.
            if ($offer->status !== Offer::STATUS_PENDING) {
                $offer->update(['responded_at' => now()]);
            }

            return $offer;
        });

        event(OfferChanged::for($offer->fresh(['listing'])));

        return $offer;
    }
}
