<x-layouts::public
    :title="__('Our sellers')"
    :description="__('Every :app seller is vetted by hand: licensed, trading under their own name, and accountable for what they list.', ['app' => config('app.name')])"
>
    <x-verum.page-head
        :title="__('Our')"
        :emphasis="__('sellers')"
        :lede="__('Vetted by hand. Licensed, trading under their own name, and accountable for every listing.')"
        :crumbs="[__('Sellers') => null]"
    />

    <div class="wrap">
        <div class="houses">
            @foreach ($sellers as $index => $seller)
                <x-verum.seller.card :$seller :$index />
            @endforeach
        </div>
    </div>

    <x-verum.seller.origins />
</x-layouts::public>
