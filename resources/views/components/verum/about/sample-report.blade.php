@php
    // Shows a real catalogue report so the "Verify it" link actually resolves.
    $sample = \App\Models\Stone::with('gemType')->where('report_number', 'VR-2026-002448')->first();
@endphp

@if ($sample)
    <aside class="sreport" data-reveal style="--d:200">
        <p class="sreport__h">
            <svg aria-hidden="true"><use href="#vm-mark" /></svg>{{ __('Sample report') }}
        </p>
        <p class="sreport__no">№ {{ $sample->report_number }}</p>
        <dl>
            <div><dt>{{ __('Species') }}</dt><dd>{{ __('Natural :type', ['type' => mb_strtolower($sample->gemType->label)]) }}</dd></div>
            <div><dt>{{ __('Weight') }}</dt><dd>{{ $sample->caratLabel() }}</dd></div>
            <div><dt>{{ __('Origin') }}</dt><dd>{{ $sample->originLabel() }}</dd></div>
            <div><dt>{{ __('Treatment') }}</dt><dd>{{ $sample->treatment }}</dd></div>
        </dl>
        <a class="link-arrow link-arrow--sm" href="{{ route('verify', ['report' => $sample->report_number]) }}" wire:navigate>
            {{ __('Verify it') }} <x-verum.icon name="arrow" />
        </a>
    </aside>
@endif
