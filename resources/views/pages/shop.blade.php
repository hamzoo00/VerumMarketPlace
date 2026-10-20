<x-layouts::public
    :title="__('Shop gemstones')"
    :description="__('Browse natural, independently certified gemstones and jewellery. Every listing names its origin and treatment.')"
>
    <x-verum.page-head
        :title="__('Shop')"
        :emphasis="__('gemstones')"
        :lede="__('Filter by family, treatment and type. Every listing ships with what the seller disclosed.')"
        :crumbs="[__('Shop') => null]"
    />

    {{--
        The browser renders server-side on first request, so the catalogue
        is fully crawlable; filtering and paging afterwards go over the wire.
    --}}
    <div class="wrap shop">
        <livewire:shop-browser />
    </div>
</x-layouts::public>
