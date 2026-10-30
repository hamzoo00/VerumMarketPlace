@props([
    'viewer',
    'account',
    'title',
    'heading',
    'lede' => null,
])

{{-- The inbox's shell is the shared account shell; Message::FROM_* are the area names. --}}
<x-verum.account.shell :$viewer :$account :$title :$heading :$lede>
    {{ $slot }}
</x-verum.account.shell>
