@props([
    'title' => null,
    'heading' => null,
    'lede' => null,
])

@php
    $admin = auth('admin')->user();
    $nav = [
        ['label' => __('Overview'), 'icon' => 'grid', 'route' => 'admin.dashboard'],
        ['label' => __('Applications'), 'icon' => 'file', 'route' => 'admin.applications'],
        ['label' => __('Cases'), 'icon' => 'shield', 'route' => 'admin.cases'],
        ['label' => __('Messages'), 'icon' => 'chat', 'route' => 'chat.index'],
        ['label' => __('Approvals'), 'icon' => 'check', 'route' => 'admin.approvals'],
        ['label' => __('Featured'), 'icon' => 'spark', 'route' => 'admin.features'],
        ['label' => __('Premium'), 'icon' => 'badge', 'route' => 'admin.premium'],
        ['label' => __('Settings'), 'icon' => 'sliders', 'route' => 'admin.settings'],
        ['label' => __('Moderation'), 'icon' => 'eye', 'route' => 'admin.moderation'],
        ['label' => __('Sellers'), 'icon' => 'gem', 'route' => 'admin.sellers'],
        ['label' => __('Buyers'), 'icon' => 'user', 'route' => 'admin.buyers'],
    ];
    $current = request()->route()?->getName();
@endphp

{{--
    Standalone staff shell.
    Deliberately does NOT extend layouts::public: the storefront header, ticker,
    cart, chat widget and footer are all customer-facing and have no place in
    the admin area.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">
<head>
    @include('partials.head', [
        'title' => $title,
        'robots' => 'noindex, nofollow',
    ])
    <script>document.documentElement.classList.replace('no-js', 'js');</script>
</head>
<body class="adminbody">
    <a class="skip" href="#main">{{ __('Skip to content') }}</a>

    <x-verum.sprite />

    <header class="adminbar">
        <a class="adminbar__brand" href="{{ route('admin.dashboard') }}" wire:navigate>
            <svg class="brand__mark" aria-hidden="true"><use href="#vm-mark" /></svg>
            <span>{{ config('app.name') }}</span>
            <span class="adminbar__tag">{{ __('Admin') }}</span>
        </a>

        <div class="adminbar__right">
            <a class="link link--quiet" href="{{ route('home') }}" target="_blank" rel="noopener">
                {{ __('View site') }}<x-verum.icon name="arrow-ur" />
            </a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn btn--ghost btn--sm" type="submit">{{ __('Sign out') }}</button>
            </form>
        </div>
    </header>

    <main id="main" tabindex="-1" class="adminshell">
        <aside class="acct-side" aria-label="{{ __('Admin navigation') }}">
            <div class="acct-side__who">
                <span class="crest crest--sm"><span class="crest__i">{{ $admin->initials() }}</span></span>
                <div>
                    <b>{{ $admin->name }}</b>
                    <small>{{ '@'.$admin->username }}</small>
                </div>
            </div>

            <nav class="acct-nav">
                @foreach ($nav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        @class(['acct-nav__link', 'is-on' => str_starts_with((string) $current, $item['route'])])
                        @if (str_starts_with((string) $current, $item['route'])) aria-current="page" @endif
                        wire:navigate
                    >
                        <x-verum.icon :name="$item['icon']" />{{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <section class="acct-main">
            <header class="acct-main__head">
                <h1 class="h2">{{ $heading }}</h1>
                @if ($lede)
                    <p class="acct-main__lede">{{ $lede }}</p>
                @endif
            </header>

            @if (session('status'))
                <div class="notice notice--ok" role="status">
                    <x-verum.icon name="check" />
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            <x-verum.form.errors />

            {{ $slot }}
        </section>
    </main>

    <x-verum.toast-host />

    @fluxScripts
</body>
</html>
