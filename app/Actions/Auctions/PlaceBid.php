<?php

namespace App\Actions\Auctions;

use App\Models\Bid;
use App\Models\Buyer;
use App\Models\Listing;
use App\Models\Money;
use App\Support\AuctionRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Places a bid, applying the house rules.
 *
 * The whole thing runs in one transaction with the listing row locked, so
 * two bidders arriving at the same instant cannot both clear the same
 * minimum. Without the lock the later bid could be accepted at a price the
 * earlier one already beat.
 */
class PlaceBid
{
    /** @throws ValidationException */
    public function handle(Listing $listing, Buyer $buyer, int $amountCents, ?string $ip = null): Bid
    {
        return DB::transaction(function () use ($listing, $buyer, $amountCents, $ip): Bid {
            /** @var Listing $locked */
            $locked = Listing::query()->lockForUpdate()->findOrFail($listing->id);

            $this->guard($locked, $buyer, $amountCents);

            // Everything the previous leader needs to know it has been beaten.
            Bid::query()
                ->where('listing_id', $locked->id)
                ->whereNull('outbid_at')
                ->update(['outbid_at' => now()]);

            $extend = AuctionRules::shouldExtend($locked);

            $bid = Bid::create([
                'listing_id' => $locked->id,
                'buyer_id' => $buyer->id,
                'amount_cents' => $amountCents,
                'currency' => $locked->currency,
                'extended_auction' => $extend,
                'ip_address' => $ip,
            ]);

            $locked->forceFill([
                'current_bid_cents' => $amountCents,
                'bids_count' => $locked->bids_count + 1,
                ...$extend ? [
                    'auction_ends_at' => AuctionRules::extendedEndsAt($locked),
                    'extensions_count' => $locked->extensions_count + 1,
                ] : [],
            ])->save();

            return $bid;
        });
    }

    /** @throws ValidationException */
    private function guard(Listing $listing, Buyer $buyer, int $amountCents): void
    {
        if (! $listing->isAuction()) {
            throw ValidationException::withMessages([
                'amount' => __('This listing is not an auction.'),
            ]);
        }

        if (! AuctionRules::isOpen($listing)) {
            throw ValidationException::withMessages([
                'amount' => __('Bidding on this lot has closed.'),
            ]);
        }

        $minimum = AuctionRules::minimumBid($listing);

        if ($amountCents < $minimum) {
            throw ValidationException::withMessages([
                'amount' => __('Your bid must be at least :amount.', [
                    'amount' => (new Money($minimum, $listing->currency))->format(),
                ]),
            ]);
        }

        // Bidding against yourself only inflates the price you pay.
        $leader = Bid::query()
            ->where('listing_id', $listing->id)
            ->whereNull('outbid_at')
            ->first();

        if ($leader !== null && $leader->buyer_id === $buyer->id) {
            throw ValidationException::withMessages([
                'amount' => __('You are already the highest bidder.'),
            ]);
        }
    }
}
