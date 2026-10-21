<?php

namespace App\Actions\Cases;

use App\Models\Buyer;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SupportCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A buyer raises a dispute or an audit.
 *
 * One action for both kinds: they differ only in what they point at, and
 * sharing the path keeps the limits and the reference format identical.
 */
class RaiseCase
{
    /** A problem with a seller the buyer dealt with. */
    public function dispute(Buyer $buyer, Seller $seller, string $reason, string $body, ?Listing $listing = null): SupportCase
    {
        return $this->open($buyer, SupportCase::KIND_DISPUTE, $reason, $body, [
            'seller_id' => $seller->id,
            'listing_id' => $listing?->id,
        ]);
    }

    /** "This listing looks wrong" — open to any signed-in buyer. */
    public function audit(Buyer $buyer, Listing $listing, string $reason, string $body): SupportCase
    {
        $already = SupportCase::query()
            ->where('buyer_id', $buyer->id)
            ->where('listing_id', $listing->id)
            ->where('kind', SupportCase::KIND_AUDIT)
            ->pending()
            ->exists();

        if ($already) {
            throw ValidationException::withMessages([
                'body' => __('You have already asked us to look at this listing.'),
            ]);
        }

        return $this->open($buyer, SupportCase::KIND_AUDIT, $reason, $body, [
            'listing_id' => $listing->id,
            // Recorded so the admin queue can group by seller without a join.
            'seller_id' => $listing->seller_id,
        ]);
    }

    /** @param  array<string, mixed>  $subject */
    private function open(Buyer $buyer, string $kind, string $reason, string $body, array $subject): SupportCase
    {
        $open = SupportCase::query()->where('buyer_id', $buyer->id)->pending()->count();

        if ($open >= (int) config('verum_cases.limits.max_open_per_buyer')) {
            throw ValidationException::withMessages([
                'body' => __('You have several open cases already. Let us settle those first.'),
            ]);
        }

        return DB::transaction(fn (): SupportCase => SupportCase::create([
            ...$subject,
            'reference' => SupportCase::newReference($kind),
            'kind' => $kind,
            'buyer_id' => $buyer->id,
            'reason' => $reason,
            'body' => $body,
            'status' => SupportCase::STATUS_OPEN,
        ]));
    }
}
