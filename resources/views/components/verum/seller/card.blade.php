@props([
    'seller',
    'index' => 0,
])

{{-- Matches the demo's .house card; the CSS for it already ships in sellers.css. --}}
<div class="cell" data-reveal style="--d:{{ min($index, 8) * 80 }}">
    <article
        class="house"
        @if ($seller->accent) style="--gem:var(--gem-{{ $seller->accent }}, currentColor)" @endif
        data-tilt
    >
        <div class="house__top">
            <x-verum.seller.crest :$seller />
            <span class="vbadge"><x-verum.icon name="shield" />{{ __('Verified') }}</span>
        </div>

        <h3 class="house__name">
            <a class="stretch" href="{{ route('sellers.show', $seller) }}" wire:navigate>{{ $seller->name }}</a>
        </h3>

        <p class="house__loc"><x-verum.icon name="pin" />{{ $seller->location }}</p>

        @if ($seller->specialties)
            <ul class="house__tags">
                @foreach ($seller->specialties as $specialty)
                    <li>{{ $specialty }}</li>
                @endforeach
            </ul>
        @endif

        <dl class="house__stats">
            <div>
                <dt>{{ __('Listings') }}</dt>
                <dd>{{ $seller->stones_count ?? $seller->listings_count }}</dd>
            </div>
            <div>
                <dt>{{ __('Sold') }}</dt>
                <dd>{{ $seller->sold_label }}</dd>
            </div>
            <div>
                <dt>{{ __('Rating') }}</dt>
                <dd>{{ number_format($seller->rating, 1) }}<span class="star">★</span></dd>
            </div>
        </dl>

        <p class="house__go">{{ __('View storefront') }}<x-verum.icon name="arrow" /></p>
    </article>
</div>
