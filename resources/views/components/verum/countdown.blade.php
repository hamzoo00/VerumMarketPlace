@props([
    'endsAt',
    'slug' => null,
])

{{--
    Server renders the correct time remaining so crawlers and no-JS visitors
    see a real value; the client then ticks it down from the datetime attribute.
--}}
<span class="scard__time" @if ($slug) data-countdown="{{ $slug }}" @endif>
    <x-verum.icon name="clock" />
    <time datetime="{{ $endsAt->toIso8601String() }}">
        @if ($endsAt->isPast())
            {{ __('Closed') }}
        @else
            {{ $endsAt->diffForHumans(['parts' => 2, 'short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
        @endif
    </time>
</span>
