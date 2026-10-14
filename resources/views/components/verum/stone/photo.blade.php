@props([
    'stone',
    'size' => 'card',
    'alt' => '',
    'large' => false,
    'eager' => false,
])

{{-- Some stones reuse another gem's photo with a CSS filter to shift its hue. --}}
<img
    src="{{ $large ? $stone->largeImageUrl() : $stone->imageUrl($size) }}"
    alt="{{ $alt }}"
    loading="{{ $eager ? 'eager' : 'lazy' }}"
    decoding="async"
    draggable="false"
    @if ($stone->tint) style="filter:{{ $stone->tint }}" @endif
    {{ $attributes }}
>
