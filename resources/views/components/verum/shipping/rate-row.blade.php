@props([
    'index',
    'rate' => null,
    'services' => [],
    'destinations' => [],
])

@php
    // Two spellings on purpose: HTML form arrays need brackets, while
    // old() and the validator address the same field with dots. Posting
    // "rates.0.service" would arrive as one flat key and be ignored.
    $field = fn (string $key) => "rates[{$index}][{$key}]";
    $path = fn (string $key) => "rates.{$index}.{$key}";
    $old = fn (string $key, $fallback = null) => old("rates.{$index}.{$key}", $fallback);
    $money = fn (?int $cents) => $cents === null ? null : number_format($cents / 100, 2, '.', '');
@endphp

{{--
    One postage option. Rows are indexed rather than keyed by id: the form
    replaces the whole table on save, so a row the seller removed simply
    stops being submitted.
--}}
<fieldset class="srate" data-rate-row>
    {{-- $index is "__INDEX__" in the clone template, so it is not arithmetic. --}}
    <legend class="sr-only">{{ __('Postage option') }}</legend>

    <div class="srate__grid">
        <x-verum.form.select
            :name="$field('service')" :error-key="$path('service')"
            :label="__('Service')"
            :options="collect($services)->map(fn ($s) => $s['label'])->all()"
            :selected="$old('service', $rate?->service)"
            :placeholder="__('Choose…')"
            required
        />

        <x-verum.form.select
            :name="$field('destination')" :error-key="$path('destination')"
            :label="__('Goes to')"
            :options="$destinations"
            :selected="$old('destination', $rate?->destination)"
            :placeholder="__('Choose…')"
            required
        />

        <x-verum.form.field
            :name="$field('price')" :error-key="$path('price')"
            prefix="$"
            :label="__('Price')"
            type="number"
            step="0.01"
            min="0"
            :value="$old('price', $money($rate?->price_cents))"
            :hint="__('0 for free')"
            required
        />

        <x-verum.form.field
            :name="$field('extra_item')" :error-key="$path('extra_item')"
            prefix="$"
            :label="__('Each extra item')"
            type="number"
            step="0.01"
            min="0"
            :value="$old('extra_item', $money($rate?->extra_item_cents))"
            :hint="__('Optional')"
        />

        <x-verum.form.field
            :name="$field('transit_min_days')" :error-key="$path('transit_min_days')"
            :label="__('Arrives from')"
            type="number"
            min="0"
            :value="$old('transit_min_days', $rate?->transit_min_days)"
            :hint="__('Days')"
            required
        />

        <x-verum.form.field
            :name="$field('transit_max_days')" :error-key="$path('transit_max_days')"
            :label="__('Arrives by')"
            type="number"
            min="0"
            :value="$old('transit_max_days', $rate?->transit_max_days)"
            :hint="__('Days')"
            required
        />
    </div>

    <div class="srate__foot">
        <div class="chklist chklist--inline">
            <label class="chk">
                <input type="hidden" name="{{ $field('tracked') }}" value="0">
                <input type="checkbox" name="{{ $field('tracked') }}" value="1" @checked($old('tracked', $rate?->tracked ?? true))>
                <span>{{ __('Tracked') }}</span>
            </label>

            <label class="chk">
                <input type="hidden" name="{{ $field('insured') }}" value="0">
                <input type="checkbox" name="{{ $field('insured') }}" value="1" @checked($old('insured', $rate?->insured ?? false))>
                <span>{{ __('Insured') }}</span>
            </label>
        </div>

        <input
            class="srate__note"
            type="text"
            name="{{ $field('note') }}"
            maxlength="120"
            value="{{ $old('note', $rate?->note) }}"
            placeholder="{{ __('Note for buyers (optional)') }}"
        >

        <button class="ibtn srate__x" type="button" data-remove-rate aria-label="{{ __('Remove this option') }}">
            <x-verum.icon name="trash" />
        </button>
    </div>
</fieldset>
