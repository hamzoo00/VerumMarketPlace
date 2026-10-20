<x-layouts::seller
    :account="$account"
    :title="__('Orders')"
    :heading="__('Orders')"
    :lede="__('Paid orders and their shipping status.')"
>
    <x-verum.account.panel :title="__('Orders')" icon="box">
        <x-verum.account.empty
            icon="box"
            :title="__('No orders yet')"
            :body="__('Paid orders will appear here with their shipping labels.')"
        />
    </x-verum.account.panel>
</x-layouts::seller>
