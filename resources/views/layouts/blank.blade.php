@props([
    'title' => null,
    'description' => null,
    'robots' => 'noindex, nofollow',
])

{{--
    Chrome-free page shell.
    Just the brand and the slot — for screens that should not carry the
    storefront header, cart or chat widget, such as the admin sign-in.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">
<head>
    @include('partials.head', ['title' => $title, 'description' => $description, 'robots' => $robots])
    <script>document.documentElement.classList.replace('no-js', 'js');</script>
</head>
<body class="blankbody">
    <x-verum.sprite />

    <header class="blankbar">
        <a class="blankbar__brand" href="{{ route('home') }}">
            <svg class="brand__mark" aria-hidden="true"><use href="#vm-mark" /></svg>
            <span>{{ config('app.name') }}</span>
        </a>
    </header>

    <main id="main" tabindex="-1">
        {{ $slot }}
    </main>

    <x-verum.toast-host />

    @fluxScripts
</body>
</html>
