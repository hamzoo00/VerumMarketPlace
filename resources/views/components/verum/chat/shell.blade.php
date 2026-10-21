@props([
    'viewer',
    'account',
    'title',
    'heading',
    'lede' => null,
])

{{--
    The inbox is shared by three audiences, each with its own shell.
    One component picks the right one so the pages themselves do not
    carry a three-way branch.
--}}
@if ($viewer === \App\Models\Message::FROM_ADMIN)
    <x-layouts::admin :$title :$heading :$lede>
        {{ $slot }}
    </x-layouts::admin>
@elseif ($viewer === \App\Models\Message::FROM_SELLER)
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
