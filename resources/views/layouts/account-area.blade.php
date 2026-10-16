@props([
    'title' => null,
    'heading' => null,
    'lede' => null,
    'nav' => [],
    'accountName' => null,
    'accountInitials' => '',
    'accountMeta' => null,
    'logoutRoute',
])

{{-- Shared shell for the private buyer and seller areas. --}}
<x-layouts::public :$title robots="noindex, nofollow">
    <div class="wrap acct-area">
        <aside class="acct-side" aria-label="{{ __('Account navigation') }}">
            <div class="acct-side__who">
                <span class="crest crest--sm"><span class="crest__i">{{ $accountInitials }}</span></span>
                <div>
                    <b>{{ $accountName }}</b>
                    @if ($accountMeta)
                        <small>{{ $accountMeta }}</small>
                    @endif
                </div>
            </div>

            <nav class="acct-nav">
                @foreach ($nav as $item)
                    <a
                        href="{{ $item['url'] }}"
                        @class(['acct-nav__link', 'is-on' => $item['active']])
                        @if ($item['active']) aria-current="page" @endif
                        wire:navigate
                    >
                        <x-verum.icon :name="$item['icon']" />{{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route($logoutRoute) }}" class="acct-side__out">
                @csrf
                <button class="btn btn--ghost btn--sm btn--block" type="submit">{{ __('Sign out') }}</button>
            </form>
        </aside>

        <section class="acct-main">
            <header class="acct-main__head">
                <h1 class="h2">{{ $heading }}</h1>
                @if ($lede)
                    <p class="acct-main__lede">{{ $lede }}</p>
                @endif
            </header>

            {{ $slot }}
        </section>
    </div>
</x-layouts::public>
