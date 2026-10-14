@php
    $live = $lots->filter(fn ($lot) => ! $lot->hasEnded());
    $closed = $lots->filter(fn ($lot) => $lot->hasEnded());
@endphp

<x-layouts::public
    :title="__('Live auctions')"
    :description="__('Bid on certified gemstones in this week\'s sale. Every lot is independently graded, with its origin and treatment disclosed.')"
>
    <x-verum.page-head
        :title="__('Live')"
        :emphasis="__('auctions')"
        :lede="__('A bid in the final two minutes extends the lot, so a late bid never steals it.')"
        :crumbs="[__('Auctions') => null]"
    />

    <div class="wrap">
        @if ($live->isNotEmpty())
            <div class="cards cards--4">
                @foreach ($live as $index => $lot)
                    <x-verum.stone.card :stone="$lot->stone" :$index />
                @endforeach
            </div>
        @else
            <div class="empty">
                <x-verum.icon name="gavel" class="empty__ic" />
                <h3>{{ __('No live lots right now.') }}</h3>
                <p>{{ __('The next sale opens shortly. Browse fixed-price stones in the meantime.') }}</p>
                <a class="btn btn--ruby btn--sm" href="{{ route('shop') }}" wire:navigate>{{ __('Shop gemstones') }}</a>
            </div>
        @endif
    </div>

    <x-verum.auction.steps />

    @if ($closed->isNotEmpty())
        <section class="sec">
            <div class="wrap">
                <x-verum.section-head :kicker="__('Results')" :title="__('Recently')" :emphasis="__('closed.')" />
                <div class="cards cards--4">
                    @foreach ($closed as $index => $lot)
                        <x-verum.stone.card :stone="$lot->stone" :$index />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts::public>
