@props([
    'kicker' => null,
    'title',
    'titleId' => null,
    'aside' => null,
    'emphasis' => null,
])

{{-- Shared section heading: kicker, split title, optional aside paragraph. --}}
<div class="sh">
    <div>
        @if ($kicker)
            <p class="kicker" data-reveal>{{ $kicker }}</p>
        @endif
        <h2 class="h2" @if ($titleId) id="{{ $titleId }}" @endif data-split>
            {{ $title }}@if ($emphasis) <em>{{ $emphasis }}</em>@endif
        </h2>
    </div>
    @if ($aside)
        <p class="sh__aside" data-reveal>{{ $aside }}</p>
    @endif
</div>
