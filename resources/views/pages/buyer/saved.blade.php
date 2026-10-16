<x-layouts::account-area
    :title="__('Saved stones')"
    :heading="__('Saved stones')"
    :lede="__('Stones you have kept for later.')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <x-verum.account.panel :title="__('Saved stones')" icon="heart">
        <x-verum.account.empty
            icon="heart"
            :title="__('Nothing saved yet')"
            :body="__('Tap the heart on any stone to keep it here.')"
        />
        <a class="btn btn--ghost btn--sm" href="{{ route('shop') }}" wire:navigate>
            {{ __('Shop gemstones') }}
        </a>
    </x-verum.account.panel>
</x-layouts::account-area>
