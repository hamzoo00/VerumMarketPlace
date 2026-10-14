@php
    $lot = $stone->auctionLot;
    $metaTitle = __(':name, :ct', ['name' => $stone->name, 'ct' => $stone->caratLabel()]);
@endphp

<x-layouts::public
    :title="$metaTitle"
    :description="Str::limit($stone->description, 155)"
    :og-image="$stone->largeImageUrl()"
    og-type="product"
>
    @push('schema')
        {{-- Product + Offer markup so listings qualify for rich results. --}}
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $stone->name,
                'description' => $stone->description,
                'image' => $stone->largeImageUrl(),
                'sku' => $stone->report_number,
                'category' => $stone->gemType->label,
                'material' => $stone->gemType->mineral,
                'weight' => [
                    '@type' => 'QuantitativeValue',
                    'value' => $stone->carat,
                    'unitText' => 'carat',
                ],
                'brand' => [
                    '@type' => 'Organization',
                    'name' => $stone->seller->name,
                ],
                'offers' => $lot
                    ? [
                        '@type' => 'Offer',
                        'url' => route('stones.show', $stone),
                        'priceCurrency' => $stone->currency,
                        'price' => number_format($lot->current_bid_cents / 100, 2, '.', ''),
                        'availability' => 'https://schema.org/InStock',
                    ]
                    : [
                        '@type' => 'Offer',
                        'url' => route('stones.show', $stone),
                        'priceCurrency' => $stone->currency,
                        'price' => number_format(($stone->price_cents ?? 0) / 100, 2, '.', ''),
                        'availability' => 'https://schema.org/InStock',
                        'itemCondition' => 'https://schema.org/NewCondition',
                    ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush

    <div class="wrap" id="pdp">
        <nav class="crumbs" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('shop') }}" wire:navigate>{{ __('Shop') }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('shop', ['type' => $stone->gemType->slug]) }}" wire:navigate>{{ $stone->gemType->label }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $stone->name }}</span>
        </nav>

        <div class="pdp__grid" style="--gem:{{ $stone->gemType->accent_color }}">
            <x-verum.stone.gallery :$stone />
            <x-verum.stone.buy-panel :$stone />
        </div>
    </div>

    <x-verum.stone.certificate :$stone />

    @if ($related->isNotEmpty())
        <section class="sec more">
            <div class="wrap">
                <x-verum.section-head
                    :kicker="__('More like this')"
                    :title="__('Other')"
                    :emphasis="$stone->gemType->plural"
                />
                <div class="cards cards--4">
                    @foreach ($related as $index => $other)
                        <x-verum.stone.card :stone="$other" :$index />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts::public>
