<x-layouts::public
    :title="$seller->name"
    :description="Str::limit($seller->about, 155)"
    og-type="profile"
>
    @push('schema')
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Store',
                'name' => $seller->name,
                'description' => $seller->about,
                'address' => ['@type' => 'PostalAddress', 'addressLocality' => $seller->location],
                'aggregateRating' => [
                    '@type' => 'AggregateRating',
                    'ratingValue' => (string) $seller->rating,
                    'reviewCount' => (string) $seller->reviews_count,
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush

    <header class="sfront wrap" style="--gem:var(--gem-{{ $seller->accent }}, currentColor)">
        <nav class="crumbs" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('sellers.index') }}" wire:navigate>{{ __('Sellers') }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $seller->name }}</span>
        </nav>

        <div class="sfront__row">
            <x-verum.seller.crest :$seller size="lg" />
            <div class="sfront__id">
                <h1 class="sfront__name" data-split>{{ $seller->name }}</h1>
                <p class="sfront__loc">
                    <x-verum.icon name="pin" />{{ $seller->location }}
                    <span class="vbadge"><x-verum.icon name="shield" />{{ __('Verified :date', ['date' => $seller->verified_on?->format('d/m/Y')]) }}</span>
                </p>
            </div>
            <a class="btn btn--ruby" href="{{ route('contact') }}" wire:navigate>
                <x-verum.icon name="mail" />{{ __('Contact Verum') }}
            </a>
        </div>

        <p class="sfront__about">{{ $seller->about }}</p>

        <ul class="sfront__stats">
            <li><b>{{ number_format($seller->rating, 1) }} ★</b><span>{{ trans_choice(':count review|:count reviews', $seller->reviews_count) }}</span></li>
            <li><b>{{ $seller->stones->count() }}</b><span>{{ __('listings here') }}</span></li>
            <li><b>{{ $seller->sold_label }}</b><span>{{ __('stones sold') }}</span></li>
            <li><b>{{ __('Since :year', ['year' => $seller->since]) }}</b><span>{{ trans_choice(':count year|:count years', $seller->years_active) }}</span></li>
        </ul>

        <p class="sfront__ship"><x-verum.icon name="box" />{{ $seller->ships }}</p>
    </header>

    <section class="sec" aria-labelledby="sellerStonesTitle">
        <div class="wrap">
            <x-verum.section-head
                :kicker="__('Listings')"
                :title="__('Stones from')"
                :emphasis="$seller->name"
                title-id="sellerStonesTitle"
            />

            @if ($seller->stones->isEmpty())
                <div class="empty">
                    <x-verum.icon name="gem" class="empty__ic" />
                    <h3>{{ __('No listings right now.') }}</h3>
                </div>
            @else
                <div class="cards cards--3">
                    @foreach ($seller->stones as $index => $stone)
                        <x-verum.stone.card :$stone :$index />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($seller->reviews->isNotEmpty())
        <section class="sec voices">
            <div class="wrap">
                <x-verum.section-head :kicker="__('Feedback')" :title="__('What buyers')" :emphasis="__('said.')" />
                <div class="quotes">
                    @foreach ($seller->reviews as $index => $review)
                        <x-verum.review-card :$review :$index />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts::public>
