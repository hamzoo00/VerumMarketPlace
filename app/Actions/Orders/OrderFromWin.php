<?php

namespace App\Actions\Orders;

use App\Models\Buyer;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Order;
use App\Support\AuctionRules;
use App\Support\Countries;

/**
 * Turns a won lot or an accepted offer into an order.
 *
 * A sale earned by bidding or negotiating has no delivery address yet —
 * nobody filled a form. Rather than invent one, the order is created
 * with the address blank and the buyer completes it; the *sale* is what
 * matters to the record, and it must exist the moment the hammer falls.
 *
 * Idempotent on purpose. An auction sweep may run twice and a seller may
 * click accept twice; neither may produce a second order for the same
 * lot and buyer.
 */
class OrderFromWin
{
    public function __construct(private readonly PlaceOrder $placeOrder) {}

    /**
     * The winning bidder's order, or null if there is nothing to create.
     */
    public function forAuction(Listing $listing): ?Order
    {
        if (! $listing->isAuction() || AuctionRules::isOpen($listing)) {
            return null;
        }

        // `bids` is append-only, so the top row is the winner.
        $top = $listing->bids()->orderByDesc('amount_cents')->orderBy('id')->first();

        if ($top === null) {
            return null;
        }

        // The reserve is the seller's floor: a lot that did not meet it
        // did not sell, however high the bidding went.
        if (! AuctionRules::reserveMet($listing)) {
            return null;
        }

        $buyer = $top->buyer;

        return $buyer === null ? null : $this->create(
            $listing,
            $buyer,
            Order::ORIGIN_AUCTION,
            (int) $top->amount_cents,
        );
    }

    /** The order behind an accepted offer. */
    public function forOffer(Offer $offer): ?Order
    {
        $listing = $offer->listing;
        $buyer = $offer->buyer;

        if ($listing === null || $buyer === null || $offer->status !== Offer::STATUS_ACCEPTED) {
            return null;
        }

        return $this->create($listing, $buyer, Order::ORIGIN_OFFER, (int) $offer->amount_cents);
    }

    /**
     * A valid ISO code, because the column is not nullable.
     *
     * A buyer's stored country may be a name or empty; neither is an
     * address, and the buyer completes the real one afterwards.
     */
    private static function countryFor(Buyer $buyer): string
    {
        $iso = strtoupper((string) $buyer->country);

        return Countries::exists($iso) ? $iso : 'US';
    }

    private function create(Listing $listing, Buyer $buyer, string $origin, int $agreedCents): Order
    {
        // One order per lot and buyer, whatever runs twice.
        $already = Order::query()
            ->where('buyer_id', $buyer->id)
            ->where('origin', $origin)
            ->whereHas('items', fn ($query) => $query->where('listing_id', $listing->id))
            ->first();

        if ($already !== null) {
            return $already;
        }

        return $this->placeOrder->handle(
            buyer: $buyer,
            listing: $listing,
            // Blank, and completed by the buyer. An invented address is
            // worse than an obviously missing one.
            address: [
                'name' => $buyer->name,
                'line1' => '',
                'city' => '',
                'country' => self::countryFor($buyer),
            ],
            origin: $origin,
            agreedPriceCents: $agreedCents,
        );
    }
}
