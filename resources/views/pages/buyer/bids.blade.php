<x-layouts::account-area
    :title="__('My bids')"
    :heading="__('My bids')"
    :lede="__('Every lot you have bid on, live and closed.')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <x-verum.account.panel :title="__('My bids')" icon="gavel">
        <x-verum.account.empty
            icon="gavel"
            :title="__('No bids yet')"
            :body="__('Place a bid on a live lot and it will appear here.')"
        />
        <a class="btn btn--ghost btn--sm" href="{{ route('auctions') }}" wire:navigate>
            {{ __('Browse live auctions') }}
        </a>
    </x-verum.account.panel>
</x-layouts::account-area>
