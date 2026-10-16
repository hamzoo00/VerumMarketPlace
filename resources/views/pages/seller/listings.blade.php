<x-layouts::account-area
    :title="__('Listings')"
    :heading="__('Listings')"
    :lede="__('Stones you have on the marketplace.')"
    :nav="$sellerNav"
    :account-name="$account->name"
    :account-initials="$account->initials()"
    :account-meta="__('Verified seller')"
    logout-route="seller.logout"
>
    <x-verum.account.panel :title="__('Listings')" icon="gem">
        <x-verum.account.empty
            icon="gem"
            :title="__('No listings yet')"
            :body="__('Listing a stone arrives with the seller tools.')"
        />
    </x-verum.account.panel>
</x-layouts::account-area>
