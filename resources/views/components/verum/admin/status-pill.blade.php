@props([
    'status',
    'archived' => false,
])

@php
    $map = [
        'submitted' => ['label' => __('Submitted'), 'tone' => 'wait'],
        'in_review' => ['label' => __('In review'), 'tone' => 'wait'],
        'on_hold' => ['label' => __('On hold'), 'tone' => 'hold'],
        'approved' => ['label' => __('Approved'), 'tone' => 'ok'],
        'rejected' => ['label' => __('Rejected'), 'tone' => 'bad'],
    ];
    $pill = $map[$status] ?? ['label' => $status, 'tone' => 'wait'];
@endphp

<span class="pill pill--{{ $pill['tone'] }}">{{ $pill['label'] }}</span>

@if ($archived)
    <span class="pill pill--muted">{{ __('Archived') }}</span>
@endif
