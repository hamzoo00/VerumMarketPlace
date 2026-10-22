@php
    $isAuction = $listing->isAuction();
    $open = $isAuction && \App\Support\AuctionRules::isOpen($listing);
@endphp

<x-layouts::public
    :title="$listing->title"
    :description="Str::limit($listing->description ?? $listing->title, 155)"
    og-type="product"
>
    @push('schema')
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $listing->title,
                'description' => $listing->description,
                'category' => $listing->category?->breadcrumb(' > '),
                'sku' => $listing->sku ?? $listing->report_number,
                'brand' => ['@type' => 'Organization', 'name' => $listing->seller?->name],
                'offers' => [
                    '@type' => 'Offer',
                    'url' => route('listings.show', $listing),
                    'priceCurrency' => $listing->currency,
                    'price' => number_format((($listing->price()?->cents) ?? 0) / 100, 2, '.', ''),
                    'availability' => 'https://schema.org/InStock',
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush

    {{-- Named so live-listing.js can tell this listing's broadcasts apart. --}}
    <div class="wrap pdp" id="pdp" data-live-listing="{{ $listing->slug }}">
        <nav class="crumbs" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('shop') }}" wire:navigate>{{ __('Shop') }}</a>
            @foreach ($listing->category?->trail() ?? [] as $node)
                <span aria-hidden="true">/</span>
                <a href="{{ route('shop', ['category' => $node->slug]) }}" wire:navigate>{{ $node->name }}</a>
            @endforeach
        </nav>

        <div class="pdp__grid">
            <x-verum.listing.gallery :$listing />

            <div class="buy">
                <p class="kicker" data-reveal>{{ $listing->category?->name }}</p>
                <h1 class="buy__title" data-split>{{ $listing->title }}</h1>

                @if ($isAuction)
                    <x-verum.auction.bid-panel :$listing :$open />
                @else
                    <p class="buy__price" data-reveal>
                        {{ $listing->price()?->withLocal() }}
                        {{-- The code is already in the figure once a
                             visitor's currency is shown beside it. --}}
                        @unless ($listing->price()?->local())
                            <small>{{ $listing->currency }}</small>
                        @endunless
                    </p>

                    <x-verum.listing.buy-actions :$listing />
                @endif

                @if ($listing->description)
                    <p class="buy__desc" data-reveal>{{ $listing->description }}</p>
                @endif

                <x-verum.listing.specs :$listing />

                <x-verum.listing.shipping :$listing />

                @if ($listing->seller)
                    <x-verum.seller.chip :seller="$listing->seller" />
                @endif

                <x-verum.listing.review-form :$listing />
                <x-verum.listing.audit-form :$listing />
            </div>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="sec more">
            <div class="wrap">
                <x-verum.section-head :kicker="__('More like this')" :title="__('Similar')" :emphasis="__('listings.')" />
                <div class="cards cards--4">
                    @foreach ($related as $index => $other)
                        <x-verum.listing.card :listing="$other" :$index />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts::public>
