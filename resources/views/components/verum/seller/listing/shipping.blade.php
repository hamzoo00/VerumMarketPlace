@props(['listing'])

@php
    $mode = old('shipping_mode', $listing->shipping_mode ?? \App\Models\Listing::SHIPPING_INHERIT);
    $price = $listing->shipping_price_cents === null
        ? null
        : number_format($listing->shipping_price_cents / 100, 2, '.', '');
@endphp

{{--
    Per-listing postage. Most items just follow the seller's rates, so
    "inherit" is the default and the override is opt-in.
--}}
<fieldset class="fgrp">
    <legend class="lbl">{{ __('Postage') }}</legend>

    <x-verum.form.radio-group
        name="shipping_mode"
        :legend="__('How is this item posted?')"
        :options="[
            \App\Models\Listing::SHIPPING_INHERIT => __('Use my shipping settings'),
            \App\Models\Listing::SHIPPING_FREE => __('Free postage for this item'),
            \App\Models\Listing::SHIPPING_CUSTOM => __('One flat price for this item'),
        ]"
        :selected="$mode"
    />

    <div data-shipping-custom @if ($mode !== \App\Models\Listing::SHIPPING_CUSTOM) hidden @endif>
        <x-verum.form.field
            name="shipping_price"
            prefix="$"
            :label="__('Flat postage price')"
            type="number"
            step="0.01"
            min="0"
            :value="old('shipping_price', $price)"
            :hint="__('Charged to anywhere you post, overriding your rates.')"
        />
    </div>

    <p class="fgrp__hint">
        <a class="link" href="{{ route('seller.shipping') }}" wire:navigate>{{ __('Edit my shipping settings') }}</a>
    </p>
</fieldset>
