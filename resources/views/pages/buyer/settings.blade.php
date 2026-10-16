<x-layouts::account-area
    :title="__('Account settings')"
    :heading="__('Settings')"
    :lede="__('Your details and security.')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <div class="apanels">
        <x-verum.account.panel :title="__('Your details')" icon="user">
            <dl class="specs">
                <div><dt>{{ __('Name') }}</dt><dd>{{ $buyer->name }}</dd></div>
                <div><dt>{{ __('Email') }}</dt><dd>{{ $buyer->email }}</dd></div>
                <div><dt>{{ __('Country') }}</dt><dd>{{ $buyer->country ?? __('Not set') }}</dd></div>
                <div>
                    <dt>{{ __('Email verified') }}</dt>
                    <dd>{{ $buyer->hasVerifiedEmail() ? __('Yes') : __('Not yet') }}</dd>
                </div>
            </dl>
        </x-verum.account.panel>

        <x-verum.account.password-form area="buyer" />
    </div>
</x-layouts::account-area>
