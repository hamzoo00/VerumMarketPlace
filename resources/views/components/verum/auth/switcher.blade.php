@props(['active' => 'buyer'])

{{--
    Buyer and seller are separate accounts, so this switches between two
    sign-in areas rather than toggling a mode on one form.
--}}
<nav class="authswitch" aria-label="{{ __('Account type') }}">
    <a
        href="{{ route('buyer.login') }}"
        @class(['authswitch__tab', 'is-on' => $active === 'buyer'])
        @if ($active === 'buyer') aria-current="page" @endif
        wire:navigate
    >
        <x-verum.icon name="bag" />
        <span><b>{{ __('Buyer') }}</b><small>{{ __('Browse, bid and buy') }}</small></span>
    </a>
    <a
        href="{{ route('seller.login') }}"
        @class(['authswitch__tab', 'is-on' => $active === 'seller'])
        @if ($active === 'seller') aria-current="page" @endif
        wire:navigate
    >
        <x-verum.icon name="gem" />
        <span><b>{{ __('Seller') }}</b><small>{{ __('List and sell stones') }}</small></span>
    </a>
</nav>
