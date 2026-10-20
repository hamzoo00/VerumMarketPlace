@props(['status'])

@php
    $tone = match ($status) {
        \App\Models\Listing::STATUS_ACTIVE => 'ok',
        \App\Models\Listing::STATUS_SOLD => 'ok',
        \App\Models\Listing::STATUS_ENDED => 'muted',
        \App\Models\Listing::STATUS_PAUSED => 'hold',
        default => 'wait',
    };
@endphp

<span class="pill pill--{{ $tone }}">
    {{ \App\Models\Listing::statusLabels()[$status] ?? $status }}
</span>
