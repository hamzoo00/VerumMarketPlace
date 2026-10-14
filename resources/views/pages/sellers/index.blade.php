<x-layouts::public
    :title="__('Our sellers')"
    :description="__('Every Verum seller is vetted by hand: licensed, trading under their own name, and accountable for what they list.')"
>
    <x-verum.page-head
        :title="__('Our')"
        :emphasis="__('sellers')"
        :lede="__('Vetted by hand. Licensed, trading under their own name, and accountable for every listing.')"
        :crumbs="[__('Sellers') => null]"
    />

    <div class="wrap">
        <div class="scells">
            @foreach ($sellers as $index => $seller)
                <x-verum.seller.card :$seller :$index />
            @endforeach
        </div>
    </div>

    <x-verum.seller.origins />
</x-layouts::public>
