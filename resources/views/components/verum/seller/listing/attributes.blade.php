@props(['listing'])

@php
    use App\Support\GemSpec;
@endphp

{{--
    Gem attributes. Optional throughout — jewellery listings skip most —
    but what is given has to mean something: the trade's own terms from a
    list, or words where only words will do. The patterns are GemSpec's,
    the same ones ListingRequest checks, so the browser and the server
    agree about every value before the form is sent.
--}}
<fieldset class="fgrp">
    <legend class="lbl">{{ __('Details') }}</legend>

    <div class="cform__grid">
        <x-verum.form.field
            name="carat"
            type="number"
            step="0.01"
            min="0.01"
            :max="GemSpec::caratMax()"
            data-decimals="2"
            :label="__('Carat weight')"
            :value="old('carat', $listing->carat)"
            :hint="__('e.g. 2.14')"
        />

        <x-verum.form.field
            name="dimensions"
            :label="__('Dimensions')"
            :value="old('dimensions', $listing->dimensions)"
            :hint="__('e.g. 8.1 × 6.4 × 4.2 mm')"
            :pattern="GemSpec::pattern('dimensions')"
            :data-pattern-message="__('Write the measurements like 8.1 × 6.4 × 4.2 mm.')"
            maxlength="40"
        />

        <x-verum.form.select
            name="shape"
            :label="__('Shape')"
            :options="GemSpec::shapes()"
            :selected="$listing->shape"
        />

        <x-verum.form.select
            name="cut"
            :label="__('Cutting style')"
            :options="GemSpec::cuttingStyles()"
            :selected="$listing->cut"
            :hint="__('How the facets are arranged, e.g. brilliant or step.')"
        />

        <x-verum.form.field
            name="color"
            :label="__('Colour')"
            :value="old('color', $listing->color)"
            :hint="__('In words, e.g. Royal blue')"
            :pattern="GemSpec::pattern('words')"
            :data-pattern-message="__('Describe the colour in words, e.g. Royal blue.')"
            maxlength="60"
        />

        <x-verum.form.select
            name="clarity"
            :label="__('Clarity')"
            :options="GemSpec::clarityGroups()"
            :selected="$listing->clarity"
        />

        <x-verum.form.field
            name="treatment"
            :label="__('Treatment')"
            :value="old('treatment', $listing->treatment)"
            :hint="__('e.g. Heated, Minor oil, None detected')"
            :pattern="GemSpec::pattern('phrase')"
            :data-pattern-message="__('Describe the treatment in words, e.g. Heated or None detected.')"
            maxlength="120"
            full
        />

        <x-verum.form.field
            name="origin"
            :label="__('Origin / mine')"
            :value="old('origin', $listing->origin)"
            :pattern="GemSpec::pattern('phrase')"
            :data-pattern-message="__('Name the place in words, e.g. Mogok.')"
            maxlength="80"
        />

        <x-verum.form.country name="origin_country" :label="__('Origin country')" :selected="old('origin_country', $listing->origin_country)" />
    </div>

    <label class="chk chk--inline">
        <input type="hidden" name="untreated" value="0">
        <input type="checkbox" name="untreated" value="1" @checked(old('untreated', $listing->untreated))>
        <span>{{ __('No treatment detected') }}</span>
    </label>
</fieldset>
