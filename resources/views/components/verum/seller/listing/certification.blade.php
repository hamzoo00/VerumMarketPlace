@props(['listing'])

<fieldset class="fgrp">
    <legend class="lbl">{{ __('Certification') }}</legend>

    <div class="cform__grid">
        <x-verum.form.field name="lab" :label="__('Laboratory')" :value="old('lab', $listing->lab)" :hint="__('e.g. GIA, AGL')" />
        <x-verum.form.field name="report_number" :label="__('Report number')" :value="old('report_number', $listing->report_number)" />
        <x-verum.form.field
            name="report_date"
            type="date"
            :label="__('Report date')"
            :value="old('report_date', $listing->report_date?->format('Y-m-d'))"
        />
    </div>
</fieldset>
