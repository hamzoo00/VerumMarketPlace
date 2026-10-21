<x-layouts::seller
    :account="$account"
    :title="__('Seller dashboard')"
    :heading="__('Welcome back, :name', ['name' => $account->name])"
    :lede="__('Your listings, lots and orders.')"
>
    {{--
        Counted from `listings` by SellerStats, and re-read over the socket
        whenever this seller's inventory changes — see live-seller.js.
    --}}
    <ul class="astats" @if ($storefront) data-seller-live="{{ $storefront->id }}" @endif>
        <x-verum.account.stat :label="__('Live listings')" :value="$stats->live" />
        <x-verum.account.stat :label="__('Active lots')" :value="$stats->activeLots" />
        <x-verum.account.stat :label="__('Orders to ship')" :value="$stats->ordersToShip" />
        <x-verum.account.stat :label="__('Drafts')" :value="$stats->drafts" />
    </ul>

    <div class="apanels">
        <x-verum.account.panel :title="__('Your listings')" icon="gem" :hint="__('Stones currently on the marketplace')">
            @if ($recent->isEmpty())
                <x-verum.account.empty
                    icon="gem"
                    :title="__('No listings yet')"
                    :body="__('Start with “Sell an item” and it will appear here.')"
                />
            @else
                <ul class="minilist">
                    @foreach ($recent as $listing)
                        <li>
                            <a href="{{ route('seller.listings.edit', $listing) }}" wire:navigate>{{ $listing->title }}</a>
                            <span>{{ $listing->category?->name }}</span>
                            <x-verum.seller.listing-status :status="$listing->status" />
                        </li>
                    @endforeach
                </ul>

                <a class="btn btn--ghost btn--sm" href="{{ route('seller.listings') }}" wire:navigate>
                    {{ __('Manage inventory') }}
                </a>
            @endif
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Orders to fulfil')" icon="box" :hint="__('Paid orders waiting to ship')">
            <x-verum.account.empty
                icon="box"
                :title="__('Nothing to ship')"
                :body="__('Paid orders will appear here with their shipping labels.')"
            />
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Your storefront')" icon="shield">
            @if ($account->storefront)
                <p>{{ __('Your public page is live.') }}</p>
                <a class="btn btn--ghost btn--sm" href="{{ route('sellers.show', $account->storefront) }}" wire:navigate>
                    {{ __('View storefront') }}
                </a>
            @else
                <x-verum.account.empty
                    icon="shield"
                    :title="__('Storefront not set up')"
                    :body="__('Your public page is created once your first listing goes live.')"
                />
            @endif
        </x-verum.account.panel>
    </div>
</x-layouts::seller>
