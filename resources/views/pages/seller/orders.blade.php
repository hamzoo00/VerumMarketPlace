<x-layouts::account-area
    :title="__('Orders')"
    :heading="__('Orders')"
    :lede="__('Paid orders and their shipping status.')"
    :nav="$sellerNav"
    :account-name="$account->name"
    :account-initials="$account->initials()"
    :account-meta="__('Verified seller')"
    logout-route="seller.logout"
>
    <x-verum.account.panel :title="__('Orders')" icon="box">
        <x-verum.account.empty
            icon="box"
            :title="__('No orders yet')"
            :body="__('Paid orders will appear here with their shipping labels.')"
        />
    </x-verum.account.panel>
</x-layouts::account-area>
