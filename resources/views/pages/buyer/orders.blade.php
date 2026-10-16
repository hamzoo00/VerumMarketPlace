<x-layouts::account-area
    :title="__('Orders')"
    :heading="__('Orders')"
    :lede="__('Purchases, payments and shipping.')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <x-verum.account.panel :title="__('Orders')" icon="box">
        <x-verum.account.empty
            icon="box"
            :title="__('No orders yet')"
            :body="__('Your purchases and tracking will show up here.')"
        />
        <a class="btn btn--ghost btn--sm" href="{{ route('shop') }}" wire:navigate>
            {{ __('Shop gemstones') }}
        </a>
    </x-verum.account.panel>
</x-layouts::account-area>
