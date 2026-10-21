<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\ShippingRate;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * The precedence between a listing's own postage setting and the seller's
 * rate table. Getting this wrong quotes a buyer a price the seller never
 * agreed to, so each branch is pinned here.
 */
beforeEach(function (): void {
    $this->seller = Seller::factory()->create([
        'handling_days' => 2,
        'free_shipping_over_cents' => 25_000,
    ]);

    $category = Category::factory()->create(['kind' => Category::KIND_STONE]);

    $this->listing = Listing::factory()->for($this->seller)->for($category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'price_cents' => 50_000,
        'currency' => 'USD',
    ]);

    $this->seller->shippingRates()->createMany([
        [
            'service' => 'standard',
            'destination' => ShippingRate::DESTINATION_DOMESTIC,
            'price_cents' => 500,
            'transit_min_days' => 3,
            'transit_max_days' => 7,
        ],
        [
            'service' => 'express',
            'destination' => ShippingRate::DESTINATION_DOMESTIC,
            'price_cents' => 1_800,
            'transit_min_days' => 1,
            'transit_max_days' => 3,
        ],
        [
            'service' => 'standard',
            'destination' => ShippingRate::DESTINATION_INTERNATIONAL,
            'price_cents' => 2_850,
            'transit_min_days' => 7,
            'transit_max_days' => 21,
        ],
    ]);
});

it('shows every zone by default, because the page cannot know where the buyer is', function (): void {
    $quote = $this->listing->shipping();

    expect($quote->options)->toHaveCount(3)
        ->and($quote->cheapest()->price_cents)->toBe(500)
        ->and($quote->byDestination()->keys()->all())
        ->toEqualCanonicalizing([
            ShippingRate::DESTINATION_DOMESTIC,
            ShippingRate::DESTINATION_INTERNATIONAL,
        ]);
});

it('narrows to one zone when asked', function (): void {
    expect($this->listing->shipping(ShippingRate::DESTINATION_INTERNATIONAL)->options)
        ->toHaveCount(1);

    expect($this->listing->shipping(ShippingRate::DESTINATION_DOMESTIC)->options)
        ->toHaveCount(2);
});

it('lets a listing marked free override the seller rates', function (): void {
    $this->listing->update(['shipping_mode' => Listing::SHIPPING_FREE]);

    $quote = $this->listing->fresh()->shipping();

    expect($quote->free)->toBeTrue()
        ->and($quote->options)->toBeEmpty();
});

it('lets a listing flat price override the seller rates', function (): void {
    $this->listing->update([
        'shipping_mode' => Listing::SHIPPING_CUSTOM,
        'shipping_price_cents' => 1_250,
    ]);

    $quote = $this->listing->fresh()->shipping();

    expect($quote->flat?->cents)->toBe(1_250)
        ->and($quote->options)->toBeEmpty();
});

it('reports no postage when the seller has published none', function (): void {
    $this->seller->shippingRates()->delete();

    expect($this->listing->fresh()->shipping()->isConfigured())->toBeFalse();
});

it('refuses two prices for the same service and zone', function (): void {
    expect(fn () => $this->seller->shippingRates()->create([
        'service' => 'standard',
        'destination' => ShippingRate::DESTINATION_DOMESTIC,
        'price_cents' => 900,
        'transit_min_days' => 1,
        'transit_max_days' => 4,
    ]))->toThrow(UniqueConstraintViolationException::class);
});
