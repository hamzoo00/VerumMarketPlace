@props([
    'title' => null,
    'heading' => null,
    'lede' => null,
    'account',
])

{{--
    Seller workspace shell.
    Grouped sidebar built from config/verum_seller_nav.php; sections that are
    not built yet render disabled rather than being hidden.
--}}
<x-layouts::public :$title robots="noindex, nofollow">
    <div class="wrap acct-area">
        <aside class="acct-side" aria-label="{{ __('Seller navigation') }}">
            <div class="acct-side__who">
                <span class="crest crest--sm"><span class="crest__i">{{ $account->initials() }}</span></span>
                <div>
                    <b>{{ $account->storefront?->name ?? $account->name }}</b>
                    <small>{{ __('Seller account') }}</small>
                </div>
            </div>

            @foreach (\App\Support\SellerNavigation::groups() as $group)
                <nav class="acct-nav" aria-label="{{ $group['label'] }}">
                    <p class="acct-nav__group">{{ $group['label'] }}</p>

                    @foreach ($group['items'] as $item)
                        @if ($item['built'])
                            <a
                                href="{{ $item['url'] }}"
                                @class(['acct-nav__link', 'is-on' => $item['active']])
                                @if ($item['active']) aria-current="page" @endif
                                wire:navigate
                            >
                                <x-verum.icon :name="$item['icon']" />{{ $item['label'] }}
                            </a>
                        @else
                            <span class="acct-nav__link is-soon" aria-disabled="true">
                                <x-verum.icon :name="$item['icon']" />{{ $item['label'] }}
                                <small>{{ __('Soon') }}</small>
                            </span>
                        @endif
                    @endforeach
                </nav>
            @endforeach

            <form method="POST" action="{{ route('seller.logout') }}" class="acct-side__out">
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

            {{-- The toast host shows session('status'); a banner here too
                 meant every seller action announced itself twice. --}}
            <x-verum.form.errors />

            {{ $slot }}
        </section>
    </div>
</x-layouts::public>
