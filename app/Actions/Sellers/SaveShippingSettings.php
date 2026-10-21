<?php

namespace App\Actions\Sellers;

use App\Events\ListingChanged;
use App\Models\Listing;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;

/**
 * Persists a seller's shipping settings and their rate table.
 *
 * The form submits the whole table, so this replaces it wholesale inside a
 * transaction: a half-applied rate table would quote a price the seller
 * never agreed to. Like SaveListing, this is the only place decimals
 * entered by a human become integer minor units.
 */
class SaveShippingSettings
{
    /** How many listings re-announce after a rate change. */
    private const BROADCAST_CAP = 50;

    /** @param  array<string, mixed>  $data */
    public function handle(Seller $seller, array $data): Seller
    {
        DB::transaction(function () use ($seller, $data): void {
            $seller->update([
                'handling_days' => (int) $data['handling_days'],
                'free_shipping_over_cents' => $this->cents($data['free_shipping_over'] ?? null),
                'shipping_note' => $data['shipping_note'] ?? null,
            ]);

            $rows = $this->rows($data['rates'] ?? []);

            // Replace rather than merge: a row the seller deleted in the
            // form must not survive because it still exists in the table.
            $seller->shippingRates()->delete();

            if ($rows !== []) {
                $seller->shippingRates()->createMany($rows);
            }
        });

        $seller->refresh();

        $this->announce($seller);

        return $seller;
    }

    /**
     * A rate change rewrites the delivery block on every listing this
     * seller has live, so each one re-announces itself.
     *
     * Capped: a seller with hundreds of listings must not flood the queue
     * from one form save. Beyond the cap an open page is simply a reload
     * behind, which is the lesser problem.
     */
    private function announce(Seller $seller): void
    {
        Listing::query()
            ->where('seller_id', $seller->id)
            ->published()
            ->with(['category', 'seller'])
            ->latest('published_at')
            ->take(self::BROADCAST_CAP)
            ->get()
            ->each(fn (Listing $listing) => event(ListingChanged::put($listing)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rates
     * @return array<int, array<string, mixed>>
     */
    private function rows(array $rates): array
    {
        $rows = [];

        foreach ($rates as $rate) {
            // A row with no service is an empty template the seller left
            // untouched, not a rate they meant to save.
            if (($rate['service'] ?? '') === '') {
                continue;
            }

            $rows[] = [
                'service' => $rate['service'],
                'destination' => $rate['destination'],
                'price_cents' => $this->cents($rate['price'] ?? 0) ?? 0,
                'extra_item_cents' => $this->cents($rate['extra_item'] ?? null),
                'transit_min_days' => (int) $rate['transit_min_days'],
                'transit_max_days' => (int) $rate['transit_max_days'],
                'tracked' => (bool) ($rate['tracked'] ?? false),
                'insured' => (bool) ($rate['insured'] ?? false),
                'note' => $rate['note'] ?? null,
                'is_active' => true,
            ];
        }

        return $rows;
    }

    private function cents(mixed $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return (int) round((float) $amount * 100);
    }
}
