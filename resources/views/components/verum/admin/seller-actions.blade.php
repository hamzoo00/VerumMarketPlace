@props([
    'account',
    // Warnings already on this account, so the next one can say its number.
    'warnings' => 0,
])

@php
    $id = 'seller-act-'.$account->id;
    $action = route('admin.sellers.update', $account);
    $name = $account->name;
@endphp

{{--
    What an admin can do to a seller. Each item is a notice form: it opens
    the message dialog, then applies without a reload. Only the actions
    that make sense for where the account stands are offered.
--}}
<div class="amenu" data-menu>
    <button
        class="amenu__dots"
        type="button"
        id="{{ $id }}-btn"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="{{ $id }}"
        aria-label="{{ __('Actions for :name', ['name' => $name]) }}"
        data-menu-toggle
    >
        <span aria-hidden="true">&vellip;</span>
    </button>

    <div class="amenu__pop" id="{{ $id }}" role="menu" aria-labelledby="{{ $id }}-btn" hidden>
        @if ($account->storefront)
            <a class="amenu__item" role="menuitem" href="{{ route('sellers.show', $account->storefront) }}" target="_blank" rel="noopener">
                <x-verum.icon name="arrow-ur" />{{ __('View storefront') }}
            </a>
            <hr class="amenu__sep">
        @endif

        @if ($account->isApproved() && ! $account->isClosed())
            <x-verum.admin.notice-form
                :$action
                method="PUT"
                kind="warning"
                :title="__('Warn :name', ['name' => $name])"
                :lede="$warnings === 0
                    ? __('This will be their first warning.')
                    : trans_choice('{1} They have :count warning already; this will be the second.|[2,*] They have :count warnings already; this will be warning :next.', $warnings, ['next' => $warnings + 1])"
                :confirm="__('Send warning')"
                role="none"
            >
                <input type="hidden" name="action" value="warn">
                <button class="amenu__item" type="submit" role="menuitem">
                    <x-verum.icon name="alert" />{{ __('Give a warning') }}
                    @if ($warnings > 0)
                        <small class="amenu__count">{{ $warnings }}</small>
                    @endif
                </button>
            </x-verum.admin.notice-form>
        @endif

        @if ($account->canTrade())
            <x-verum.admin.notice-form
                :$action
                method="PUT"
                kind="suspended"
                :title="__('Suspend :name’s selling', ['name' => $name])"
                :lede="__('Their storefront and listings disappear from the site at once. Orders already placed carry on.')"
                :confirm="__('Suspend selling')"
                danger
                role="none"
            >
                <input type="hidden" name="action" value="suspend">
                <button class="amenu__item" type="submit" role="menuitem">
                    <x-verum.icon name="pause" />{{ __('Cancel approval (suspend)') }}
                </button>
            </x-verum.admin.notice-form>
        @elseif ($account->isSuspended() && ! $account->isClosing() && ! $account->isClosed())
            <x-verum.admin.notice-form
                :$action
                method="PUT"
                kind="reinstated"
                :title="__('Reinstate :name', ['name' => $name])"
                :lede="__('Their storefront and listings come back exactly as they were.')"
                :confirm="__('Reinstate')"
                role="none"
            >
                <input type="hidden" name="action" value="reinstate">
                <button class="amenu__item amenu__item--ok" type="submit" role="menuitem">
                    <x-verum.icon name="check" />{{ __('Reinstate selling') }}
                </button>
            </x-verum.admin.notice-form>
        @endif

        @if ($account->isApproved() && $account->closing_started_at === null)
            <hr class="amenu__sep">

            <x-verum.admin.notice-form
                :$action
                method="PUT"
                kind="closing"
                :replace="['date' => __('the date their last open order settles')]"
                :title="__('Close :name’s account', ['name' => $name])"
                :lede="__('Undispatched orders are cancelled and those buyers get an apology. Orders on their way keep the account open until they can arrive and be disputed; then it closes for good and the email cannot apply again.')"
                :confirm="__('Close account')"
                danger
                role="none"
            >
                <input type="hidden" name="action" value="close">
                <button class="amenu__item amenu__item--bad" type="submit" role="menuitem">
                    <x-verum.icon name="trash" />{{ __('Delete account') }}
                </button>
            </x-verum.admin.notice-form>
        @endif
    </div>
</div>
