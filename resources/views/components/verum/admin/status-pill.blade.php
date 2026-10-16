@props(['status'])

@php
    $map = [
        'submitted' => ['label' => __('Submitted'), 'tone' => 'wait'],
        'in_review' => ['label' => __('In review'), 'tone' => 'wait'],
        'approved' => ['label' => __('Approved'), 'tone' => 'ok'],
        'rejected' => ['label' => __('Rejected'), 'tone' => 'bad'],
    ];
    $pill = $map[$status] ?? ['label' => $status, 'tone' => 'wait'];
@endphp

<span class="pill pill--{{ $pill['tone'] }}">{{ $pill['label'] }}</span>
