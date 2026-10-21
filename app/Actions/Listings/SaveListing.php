<?php

namespace App\Actions\Listings;

use App\Models\Listing;
use App\Models\Seller;
use Illuminate\Support\Str;

/**
 * Turns validated listing input into a persisted listing.
 *
 * The form collects decimal prices because that is what a seller types; this
 * is the single place they become integer minor units.
 */
class SaveListing
{
    /** @param  array<string, mixed>  $data */
    public function create(Seller $seller, array $data): Listing
    {
        return Listing::create([
            ...$this->attributes($data),
            'seller_id' => $seller->id,
            'slug' => $this->uniqueSlug($data['title']),
            'status' => Listing::STATUS_DRAFT,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Listing $listing, array $data): Listing
    {
        $listing->update($this->attributes($data));

        return $listing->refresh();
    }

    /** Draft → live. Auctions keep their own end time. */
    public function publish(Listing $listing): Listing
    {
        $listing->update([
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => $listing->published_at ?? now(),
        ]);

        return $listing->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $isAuction = ($data['listing_type'] ?? null) === Listing::TYPE_AUCTION;
        $shippingMode = $data['shipping_mode'] ?? Listing::SHIPPING_INHERIT;
        $acceptsOffers = (bool) ($data['accepts_offers'] ?? false);

        return [
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'listing_type' => $data['listing_type'],
            'currency' => $data['currency'] ?? 'USD',
            'quantity' => $data['quantity'] ?? 1,

            // Offers: the thresholds only mean anything when offers are on,
            // so they are cleared with the switch.
            'accepts_offers' => $acceptsOffers,
            'auto_accept_cents' => $acceptsOffers ? $this->cents($data['auto_accept'] ?? null) : null,
            'auto_decline_cents' => $acceptsOffers ? $this->cents($data['auto_decline'] ?? null) : null,

            'shipping_mode' => $shippingMode,
            // Only a flat price has a figure; the other modes must not
            // leave a stale one behind, exactly as the price columns do.
            'shipping_price_cents' => $shippingMode === Listing::SHIPPING_CUSTOM
                ? ($this->cents($data['shipping_price'] ?? null) ?? 0)
                : null,

            // Only the columns that apply to this type are written; the
            // others are cleared so a type change cannot leave stale prices.
            'price_cents' => $isAuction ? null : $this->cents($data['price'] ?? null),
            'start_price_cents' => $isAuction ? $this->cents($data['start_price'] ?? null) : null,
            'reserve_price_cents' => $isAuction ? $this->cents($data['reserve_price'] ?? null) : null,
            'bid_increment_cents' => $isAuction ? $this->cents($data['bid_increment'] ?? null) : null,
            'auction_ends_at' => $isAuction ? ($data['auction_ends_at'] ?? null) : null,

            'carat' => $data['carat'] ?? null,
            'dimensions' => $data['dimensions'] ?? null,
            'shape' => $data['shape'] ?? null,
            'cut' => $data['cut'] ?? null,
            'color' => $data['color'] ?? null,
            'clarity' => $data['clarity'] ?? null,
            'treatment' => $data['treatment'] ?? null,
            'untreated' => (bool) ($data['untreated'] ?? false),
            'origin' => $data['origin'] ?? null,
            'origin_country' => $data['origin_country'] ?? null,

            'lab' => $data['lab'] ?? null,
            'report_number' => $data['report_number'] ?? null,
            'report_date' => $data['report_date'] ?? null,
        ];
    }

    private function cents(mixed $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return (int) round((float) $amount * 100);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'listing';
        $slug = $base;
        $suffix = 2;

        while (Listing::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
