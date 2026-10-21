<?php

namespace App\Actions\Offers;

use App\Events\OfferChanged;
use App\Models\Offer;
use App\Support\OfferRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The seller answers: accept, decline, or counter.
 *
 * One action for all three so the web panel, an API and a console
 * command cannot drift — the same shape as DecideSellerApplication.
 */
class RespondToOffer
{
    public function accept(Offer $offer): Offer
    {
        return $this->settle($offer, Offer::STATUS_ACCEPTED);
    }

    public function decline(Offer $offer): Offer
    {
        return $this->settle($offer, Offer::STATUS_DECLINED);
    }

    /** A counter closes the buyer's offer and opens the seller's. */
    public function counter(Offer $offer, int $amountCents, ?string $message = null): Offer
    {
        $listing = $offer->listing;

        if ($listing === null || $amountCents <= 0 || $amountCents > (int) $listing->price_cents) {
            throw ValidationException::withMessages([
                'amount' => __('A counter must be below your asking price.'),
            ]);
        }

        $counter = DB::transaction(function () use ($offer, $amountCents, $message): Offer {
            $live = $this->lockLive($offer);

            if (OfferRules::roundsExhausted($live->round + 1)) {
                throw ValidationException::withMessages([
                    'amount' => __('This negotiation has run its course.'),
                ]);
            }

            $live->update([
                'status' => Offer::STATUS_COUNTERED,
                'responded_at' => now(),
            ]);

            return Offer::create([
                'listing_id' => $live->listing_id,
                'buyer_id' => $live->buyer_id,
                'seller_id' => $live->seller_id,
                'thread_id' => $live->thread_id,
                'round' => $live->round + 1,
                'from' => Offer::FROM_SELLER,
                'amount_cents' => $amountCents,
                'currency' => $live->currency,
                'message' => $message,
                'status' => Offer::STATUS_PENDING,
                'expires_at' => OfferRules::expiresAt(),
            ]);
        });

        event(OfferChanged::for($counter->fresh(['listing'])));

        return $counter;
    }

    /** The buyer pulls their own offer before it is answered. */
    public function withdraw(Offer $offer): Offer
    {
        return $this->settle($offer, Offer::STATUS_WITHDRAWN);
    }

    private function settle(Offer $offer, string $status): Offer
    {
        $settled = DB::transaction(function () use ($offer, $status): Offer {
            $live = $this->lockLive($offer);

            $live->update(['status' => $status, 'responded_at' => now()]);

            return $live;
        });

        event(OfferChanged::for($settled->fresh(['listing'])));

        return $settled;
    }

    /**
     * Re-read under a lock and confirm it is still answerable.
     *
     * Both sides can act at once, and an offer can lapse between the page
     * rendering and the click, so the check belongs here rather than in
     * whatever was on screen.
     */
    private function lockLive(Offer $offer): Offer
    {
        $live = Offer::query()->lockForUpdate()->find($offer->id);

        if ($live === null || ! $live->isLive()) {
            throw ValidationException::withMessages([
                'offer' => __('That offer is no longer open.'),
            ]);
        }

        return $live;
    }
}
