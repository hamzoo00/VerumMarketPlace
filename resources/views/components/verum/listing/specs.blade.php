@props(['listing'])

@php
    $rows = array_filter([
        __('Weight') => $listing->caratLabel(),
        __('Dimensions') => $listing->dimensions,
        __('Shape') => $listing->shape,
        __('Cut') => $listing->cut,
        __('Colour') => $listing->color,
        __('Clarity') => $listing->clarity,
        __('Treatment') => $listing->untreated ? __('None detected') : $listing->treatment,
        __('Origin') => $listing->origin,
        __('Laboratory') => $listing->lab,
        __('Report') => $listing->report_number,
    ]);
@endphp

@if ($rows !== [])
    <dl class="specs" data-reveal>
        @foreach ($rows as $label => $value)
            <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
    </dl>
@endif
