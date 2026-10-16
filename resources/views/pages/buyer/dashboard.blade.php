<x-layouts::account-area
    :title="__('Buyer dashboard')"
    :heading="__('Welcome back, :name', ['name' => $buyer->name])"
    :lede="__('Your bids, saved stones and orders in one place.')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <ul class="astats">
        <x-verum.account.stat :label="__('Saved stones')" value="0" />
        <x-verum.account.stat :label="__('Active bids')" value="0" />
        <x-verum.account.stat :label="__('Orders')" value="0" />
    </ul>

    <div class="apanels">
        <x-verum.account.panel :title="__('Active bids')" icon="gavel" :hint="__('Lots you are currently bidding on')">
            <x-verum.account.empty
                icon="gavel"
                :title="__('No active bids')"
                :body="__('Place a bid on a live lot and it will appear here.')"
            />
            <a class="btn btn--ghost btn--sm" href="{{ route('auctions') }}" wire:navigate>
                {{ __('Browse live auctions') }}
            </a>
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Saved stones')" icon="heart" :hint="__('Stones you have kept for later')">
            <x-verum.account.empty
                icon="heart"
                :title="__('Nothing saved yet')"
                :body="__('Tap the heart on any stone to keep it here.')"
            />
            <a class="btn btn--ghost btn--sm" href="{{ route('shop') }}" wire:navigate>
                {{ __('Shop gemstones') }}
            </a>
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Recent orders')" icon="box" :hint="__('Purchases and their shipping status')">
            <x-verum.account.empty
                icon="box"
                :title="__('No orders yet')"
                :body="__('Your purchases and tracking will show up here.')"
            />
        </x-verum.account.panel>
    </div>
</x-layouts::account-area>
