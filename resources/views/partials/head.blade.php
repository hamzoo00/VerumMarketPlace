<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="theme-color" content="#071321" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

@php
    $pageTitle = filled($title ?? null)
        ? $title.' - '.config('app.name')
        : config('app.name');
    $pageDescription = $description ?? config('app.description', '');
@endphp

<title>{{ $pageTitle }}</title>

@if (filled($pageDescription))
    <meta name="description" content="{{ $pageDescription }}" />
@endif

{{-- Listing pages are the SEO surface; let them override indexing per-page. --}}
<meta name="robots" content="{{ $robots ?? 'index, follow' }}" />
<link rel="canonical" href="{{ $canonical ?? url()->current() }}" />

<meta property="og:site_name" content="{{ config('app.name') }}" />
<meta property="og:title" content="{{ $pageTitle }}" />
<meta property="og:type" content="{{ $ogType ?? 'website' }}" />
<meta property="og:url" content="{{ $canonical ?? url()->current() }}" />
@if (filled($pageDescription))
    <meta property="og:description" content="{{ $pageDescription }}" />
@endif
@if (filled($ogImage ?? null))
    <meta property="og:image" content="{{ $ogImage }}" />
@endif

<meta name="twitter:card" content="{{ filled($ogImage ?? null) ? 'summary_large_image' : 'summary' }}" />

{{-- Per-page JSON-LD (Product / Offer for listings, BreadcrumbList for categories). --}}
@stack('schema')

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=DM+Mono:wght@300;400;500&family=Manrope:wght@300..800&display=swap">


@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
