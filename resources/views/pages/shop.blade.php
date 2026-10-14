<x-layouts::public
    :title="__('Shop gemstones')"
    :description="__('Browse natural, independently certified gemstones. Filter by stone, treatment and price. Every listing ships with its grading report.')"
>
    <x-verum.page-head
        :title="__('Shop')"
        :emphasis="__('gemstones')"
        :lede="__('Filter by stone, origin and cut. Every listing ships with its grading report.')"
        :crumbs="[__('Shop') => null]"
    />

    <div class="wrap shop">
        <x-verum.shop.filters :$gemTypes :$activeType :$untreatedOnly :$activeSort />
        <div class="filters__scrim" data-close-filters></div>

        <div class="results">
            <x-verum.shop.toolbar
                :total="$stones->total()"
                :$activeSort
                :$activeType
                :$untreatedOnly
            />

            @if ($stones->isEmpty())
                <div class="empty">
                    <x-verum.icon name="loupe" class="empty__ic" />
                    <h3>{{ __('No stones match those filters.') }}</h3>
                    <p>{{ __('Loosen a filter, or clear them all to see every stone.') }}</p>
                    <a class="btn btn--ruby btn--sm" href="{{ route('shop') }}" wire:navigate>
                        {{ __('Clear filters') }}
                    </a>
                </div>
            @else
                <div class="cards cards--shop" id="shopGrid">
                    @foreach ($stones as $index => $stone)
                        <x-verum.stone.card :$stone :$index />
                    @endforeach
                </div>

                <div class="pager">
                    {{ $stones->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::public>
