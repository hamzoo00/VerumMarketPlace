@props(['listing'])

@php
    $money = fn (?int $cents) => $cents === null ? null : number_format($cents / 100, 2, '.', '');
    $on = (bool) old('accepts_offers', $listing->accepts_offers);
@endphp

{{-- Offers are opt-in per item, as on every marketplace that has them. --}}
<fieldset class="fgrp">
    <legend class="lbl">{{ __('Offers') }}</legend>

    <div class="chklist">
        <label class="chk">
            <input type="hidden" name="accepts_offers" value="0">
            <input type="checkbox" name="accepts_offers" value="1" @checked($on) data-offers-toggle>
            <span>{{ __('Let buyers make an offer on this item') }}</span>
        </label>
    </div>

    <div data-offers-fields @unless ($on) hidden @endunless>
        <div class="cform__grid">
            <x-verum.form.field
                name="auto_accept"
                :label="__('Accept automatically at')"
                prefix="$"
                type="number"
                step="0.01"
                min="0"
                :value="old('auto_accept', $money($listing->auto_accept_cents))"
                :hint="__('Anything this high or above is taken without asking you.')"
            />

            <x-verum.form.field
                name="auto_decline"
                :label="__('Decline automatically below')"
                prefix="$"
                type="number"
                step="0.01"
                min="0"
                :value="old('auto_decline', $money($listing->auto_decline_cents))"
                :hint="__('Keeps derisory offers out of your queue.')"
            />
        </div>
    </div>
</fieldset>
