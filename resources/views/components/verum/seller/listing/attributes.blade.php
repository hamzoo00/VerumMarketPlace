@props(['listing'])

{{-- Gem attributes. Optional throughout: jewellery listings skip most. --}}
<fieldset class="fgrp">
    <legend class="lbl">{{ __('Details') }}</legend>

    <div class="cform__grid">
        <x-verum.form.field name="carat" type="number" step="0.01" :label="__('Carat weight')" :value="old('carat', $listing->carat)" />
        <x-verum.form.field name="dimensions" :label="__('Dimensions')" :value="old('dimensions', $listing->dimensions)" :hint="__('e.g. 8.1 × 6.4 × 4.2 mm')" />
        <x-verum.form.field name="shape" :label="__('Shape')" :value="old('shape', $listing->shape)" />
        <x-verum.form.field name="cut" :label="__('Cut')" :value="old('cut', $listing->cut)" />
        <x-verum.form.field name="color" :label="__('Colour')" :value="old('color', $listing->color)" />
        <x-verum.form.field name="clarity" :label="__('Clarity')" :value="old('clarity', $listing->clarity)" />
        <x-verum.form.field name="treatment" :label="__('Treatment')" :value="old('treatment', $listing->treatment)" full />
        <x-verum.form.field name="origin" :label="__('Origin / mine')" :value="old('origin', $listing->origin)" />
        <x-verum.form.country name="origin_country" :label="__('Origin country')" :selected="old('origin_country', $listing->origin_country)" />
    </div>

    <label class="chk chk--inline">
        <input type="hidden" name="untreated" value="0">
        <input type="checkbox" name="untreated" value="1" @checked(old('untreated', $listing->untreated))>
        <span>{{ __('No treatment detected') }}</span>
    </label>
</fieldset>
