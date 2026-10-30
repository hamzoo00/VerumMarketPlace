@props([
    // 'buyer', 'seller' or 'admin' — the area the page belongs to.
    'viewer',
    'account',
    'title',
    'heading',
    'lede' => null,
])

{{--
    One page shared by three audiences, each with its own shell: the
    inbox and the notifications page both render through here, so neither
    carries a three-way branch of its own.
--}}
@if ($viewer === 'admin')
    <x-layouts::admin :$title :$heading :$lede>
        {{ $slot }}
    </x-layouts::admin>
@elseif ($viewer === 'seller')
    <x-layouts::seller :$account :$title :$heading :$lede>
        {{ $slot }}
    </x-layouts::seller>
@else
    <x-layouts::account-area
        :$title
        :$heading
        :$lede
        :nav="$buyerNav"
        :account-name="$account->name"
        :account-initials="$account->initials()"
        :account-meta="__('Buyer account')"
        logout-route="buyer.logout"
    >
        {{ $slot }}
    </x-layouts::account-area>
@endif
