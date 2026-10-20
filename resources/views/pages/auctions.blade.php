<x-layouts::public
    :title="__('Live auctions')"
    :description="__('Bid on certified gemstones. Every lot is independently graded, with its origin and treatment disclosed.')"
>
    <x-verum.page-head
        :title="__('Live')"
        :emphasis="__('auctions')"
        :lede="__('A bid in the final two minutes extends the lot, so a late bid never steals it.')"
        :crumbs="[__('Auctions') => null]"
    />

    <div class="wrap">
        {{--
            The grid is always rendered, even when empty: a lot going live
            over the socket needs somewhere to land.
        --}}
        <div class="cards cards--4" data-live-grid data-live-type="auction">
            @foreach ($live as $index => $listing)
                <x-verum.listing.card :$listing :$index />
            @endforeach
        </div>

        @if ($live->isEmpty())
            <div class="empty" data-live-empty>
                <x-verum.icon name="gavel" class="empty__ic" />
                <h3>{{ __('No live lots right now.') }}</h3>
                <p>{{ __('The next sale opens shortly. Browse fixed-price stones in the meantime.') }}</p>
                <a class="btn btn--ruby btn--sm" href="{{ route('shop') }}" wire:navigate>{{ __('Shop gemstones') }}</a>
            </div>
        @endif
    </div>

    <x-verum.auction.rules />

    @if ($closed->isNotEmpty())
        <section class="sec">
            <div class="wrap">
                <x-verum.section-head :kicker="__('Results')" :title="__('Recently')" :emphasis="__('closed.')" />
                <div class="cards cards--4">
                    @foreach ($closed as $index => $listing)
                        <x-verum.listing.card :$listing :$index />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts::public>
