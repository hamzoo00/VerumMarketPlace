@props([
    'seller',
    'index' => 0,
])

<article class="scell" data-reveal style="--d:{{ min($index, 8) * 70 }}">
    <a class="scell__link" href="{{ route('sellers.show', $seller) }}" wire:navigate>
        <header class="scell__head">
            <x-verum.seller.crest :$seller />
            <div>
                <h3 class="scell__name">{{ $seller->name }}</h3>
                <p class="scell__loc"><x-verum.icon name="pin" />{{ $seller->location }}</p>
            </div>
            <span class="vbadge"><x-verum.icon name="shield" />{{ __('Verified') }}</span>
        </header>

        <p class="scell__about">{{ Str::limit($seller->about, 150) }}</p>

        <ul class="scell__stats">
            <li><b>{{ number_format($seller->rating, 1) }} ★</b><span>{{ trans_choice(':count review|:count reviews', $seller->reviews_count) }}</span></li>
            <li><b>{{ $seller->stones_count ?? $seller->listings_count }}</b><span>{{ __('listings') }}</span></li>
            <li><b>{{ $seller->sold_label }}</b><span>{{ __('sold') }}</span></li>
            <li><b>{{ $seller->years_active }}</b><span>{{ __('years') }}</span></li>
        </ul>

        @if ($seller->specialties)
            <p class="scell__tags">
                @foreach ($seller->specialties as $specialty)
                    <span class="tag tag--soft">{{ $specialty }}</span>
                @endforeach
            </p>
        @endif
    </a>
</article>
