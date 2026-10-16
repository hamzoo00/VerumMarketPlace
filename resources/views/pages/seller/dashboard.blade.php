<x-layouts::account-area
    :title="__('Seller dashboard')"
    :heading="__('Welcome back, :name', ['name' => $account->name])"
    :lede="__('Your listings, lots and orders.')"
    :nav="$sellerNav"
    :account-name="$account->name"
    :account-initials="$account->initials()"
    :account-meta="__('Verified seller')"
    logout-route="seller.logout"
>
    <ul class="astats">
        <x-verum.account.stat :label="__('Live listings')" value="0" />
        <x-verum.account.stat :label="__('Active lots')" value="0" />
        <x-verum.account.stat :label="__('Orders to ship')" value="0" />
        <x-verum.account.stat :label="__('This month')" value="—" />
    </ul>

    <div class="apanels">
        <x-verum.account.panel :title="__('Your listings')" icon="gem" :hint="__('Stones currently on the marketplace')">
            <x-verum.account.empty
                icon="gem"
                :title="__('No listings yet')"
                :body="__('Listing a stone arrives with the seller tools.')"
            />
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
</x-layouts::account-area>
