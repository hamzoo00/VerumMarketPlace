<x-layouts::account-area
    :title="__('Settings')"
    :heading="__('Settings')"
    :lede="__('Your account and payout details.')"
    :nav="$sellerNav"
    :account-name="$account->name"
    :account-initials="$account->initials()"
    :account-meta="__('Verified seller')"
    logout-route="seller.logout"
>
    <div class="apanels">
        <x-verum.account.panel :title="__('Your details')" icon="user">
            <dl class="specs">
                <div><dt>{{ __('Name') }}</dt><dd>{{ $account->name }}</dd></div>
                <div><dt>{{ __('Email') }}</dt><dd>{{ $account->email }}</dd></div>
                <div><dt>{{ __('Country') }}</dt><dd>{{ $account->country ?? __('Not set') }}</dd></div>
                <div>
                    <dt>{{ __('Approved') }}</dt>
                    <dd>{{ $account->isApproved() ? $account->approved_at->format('d/m/Y') : __('Pending') }}</dd>
                </div>
            </dl>
        </x-verum.account.panel>

        <x-verum.account.password-form area="seller" />
    </div>
</x-layouts::account-area>
