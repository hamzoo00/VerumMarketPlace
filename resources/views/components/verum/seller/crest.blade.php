@props([
    'seller',
    'size' => '',
])

@php
    $avatar = $seller->avatarUrl();
@endphp

{{-- The seller's own avatar when they have uploaded one, otherwise an
     initials crest tinted by their signature stone. --}}
<span
    @class(['crest', 'crest--'.$size => $size !== '', 'crest--photo' => $avatar !== null])
    @if ($seller->accent) style="--gem:var(--gem-{{ $seller->accent }}, currentColor)" @endif
>
    @if ($avatar)
        <img src="{{ $avatar }}" alt="" loading="lazy" decoding="async">
    @else
        <span class="crest__i">{{ $seller->initials }}</span>
    @endif
</span>
