@props(['listing'])

@php
    $isAuction = old('listing_type', $listing->listing_type) === \App\Models\Listing::TYPE_AUCTION;
    $decimal = fn (?int $cents): ?string => $cents === null ? null : number_format($cents / 100, 2, '.', '');
@endphp

{{--
    Price fields follow the chosen type. Both sets are rendered and the
    irrelevant one is hidden, so a validation redisplay keeps what was typed
    and the form still works with JavaScript off.
--}}
<fieldset class="fgrp" data-pricing data-type-source="f-listing_type">
    <legend class="lbl">{{ __('Price') }}</legend>

    <div class="cform__grid">
        <x-verum.form.select
            name="currency"
            :label="__('Currency')"
            :options="['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'AED' => 'AED']"
            :selected="old('currency', $listing->currency ?? 'USD')"
            required
        />

        <x-verum.form.field
            name="quantity"
            type="number"
            :label="__('Quantity')"
            :value="old('quantity', $listing->quantity ?? 1)"
            min="1"
            max="999"
            required
        />
    </div>

    <div class="pricing__set" data-pricing-set="fixed" @if ($isAuction) hidden @endif>
        <x-verum.form.field
            name="price"
            prefix="$"
            type="number"
            step="0.01"
            :label="__('Price')"
            :value="old('price', $decimal($listing->price_cents))"
            full
        />
    </div>

    <div class="pricing__set" data-pricing-set="auction" @unless ($isAuction) hidden @endunless>
        <div class="cform__grid">
            <x-verum.form.field
                name="start_price"
                prefix="$"
                type="number"
                step="0.01"
                :label="__('Starting price')"
                :value="old('start_price', $decimal($listing->start_price_cents))"
            />

            <x-verum.form.field
                name="reserve_price"
                prefix="$"
                type="number"
                step="0.01"
                :label="__('Reserve (optional)')"
                :value="old('reserve_price', $decimal($listing->reserve_price_cents))"
                :hint="__('The lot will not sell below this.')"
            />

            <x-verum.form.field
                name="bid_increment"
                prefix="$"
                type="number"
                step="0.01"
                :label="__('Bid increment')"
                :value="old('bid_increment', $decimal($listing->bid_increment_cents))"
            />

            <x-verum.form.field
                name="auction_ends_at"
                type="datetime-local"
                :label="__('Ends at')"
                :value="old('auction_ends_at', $listing->auction_ends_at?->format('Y-m-d\TH:i'))"
            />
        </div>
    </div>
</fieldset>
