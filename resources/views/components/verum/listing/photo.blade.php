@props([
    'listing',
    'size' => 'card',
    'eager' => false,
])

@php
    // A seller's own photographs win. Without any the listing borrows the
    // artwork for its gem family, falling back to a drawn plate.
    $shots = $listing->imageUrls($size === 'card' ? 'thumb' : '');
    // rootName() reads a cached lookup; ancestors() cost a query per card.
    $family = \Illuminate\Support\Str::slug($listing->category?->rootName() ?? '');
    $known = ['ruby', 'sapphire', 'emerald', 'amethyst', 'topaz'];
    $key = in_array($family, $known, true) ? $family : null;
@endphp

@if ($shots !== [])
    {{--
        Every shot is in the markup so a crawler sees them all; only the
        active one is painted. More than one turns the plate into a slideshow.
    --}}
    <div
        @class(['shots', 'shots--multi' => count($shots) > 1])
        @if (count($shots) > 1) data-shots @endif
    >
        @foreach ($shots as $i => $src)
            <img
                @class(['shots__img', 'is-on' => $i === 0])
                src="{{ $src }}"
                alt="{{ $i === 0 ? $listing->title : __(':title — view :n', ['title' => $listing->title, 'n' => $i + 1]) }}"
                loading="{{ $eager && $i === 0 ? 'eager' : 'lazy' }}"
                decoding="async"
                draggable="false"
            >
        @endforeach

        @if (count($shots) > 1)
            <span class="shots__dots" aria-hidden="true">
                @foreach ($shots as $i => $src)
                    <i @class(['is-on' => $i === 0])></i>
                @endforeach
            </span>
        @endif
    </div>
@elseif ($key)
    <img
        src="{{ asset("images/gems/{$key}-{$size}.webp") }}"
        alt="{{ $listing->title }}"
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        decoding="async"
        draggable="false"
    >
@else
    <span class="plate__drawn" aria-hidden="true"></span>
@endif
