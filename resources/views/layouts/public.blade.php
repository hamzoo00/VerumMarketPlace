@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'ogImage' => null,
    'ogType' => 'website',
    'robots' => null,
    'bodyClass' => null,
])

{{--
    Public marketplace layout: the crawlable storefront chrome.
    Guests must be able to render every page that uses it, so nothing here
    assumes an authenticated user.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">
<head>
    @include('partials.head', [
        'title' => $title,
        'description' => $description,
        'canonical' => $canonical,
        'ogImage' => $ogImage,
        'ogType' => $ogType,
        'robots' => $robots,
    ])
    <script>document.documentElement.classList.replace('no-js', 'js');</script>
</head>
<body @class([$bodyClass])>
    <a class="skip" href="#main">{{ __('Skip to content') }}</a>

    <x-verum.sprite />
    <x-verum.header />

    <main id="main" tabindex="-1">
        {{ $slot }}
    </main>

    <x-verum.footer />
    <x-verum.overlays />

    @fluxScripts
</body>
</html>
