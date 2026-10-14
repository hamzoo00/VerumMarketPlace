@props([
    'seller',
    'size' => '',
])

{{-- Initials crest, tinted by the seller's signature stone. --}}
<span @class(['crest', 'crest--'.$size => $size !== '']) @if ($seller->accent) style="--gem:var(--gem-{{ $seller->accent }}, currentColor)" @endif>
    <span class="crest__i">{{ $seller->initials }}</span>
</span>
