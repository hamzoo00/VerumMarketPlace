@props(['listing'])

@php
    use App\Support\GemSpec;
@endphp

<fieldset class="fgrp">
    <legend class="lbl">{{ __('Certification') }}</legend>

    <div class="cform__grid">
        <x-verum.form.select
            name="lab"
            :label="__('Laboratory')"
            :options="GemSpec::labs()"
            :selected="$listing->lab"
        />

        <x-verum.form.field
            name="report_number"
            :label="__('Report number')"
            :value="old('report_number', $listing->report_number)"
            :hint="__('Exactly as printed on the report')"
            :pattern="GemSpec::pattern('report_number')"
            :data-pattern-message="__('Use the number exactly as printed on the report.')"
            maxlength="40"
        />

        <x-verum.form.field
            name="report_date"
            type="date"
            :max="now()->toDateString()"
            :label="__('Report date')"
            :value="old('report_date', $listing->report_date?->format('Y-m-d'))"
        />
    </div>
</fieldset>
