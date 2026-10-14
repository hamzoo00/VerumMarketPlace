{{--
    Public-facing layout for crawlable marketplace pages (home, listings,
    categories, seller storefronts). Unlike layouts::app it must render for
    guests, so it never assumes an authenticated user.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head')
</head>
<body class="min-h-dvh bg-white font-sans antialiased dark:bg-neutral-900 dark:text-white">
    <header class="border-b border-neutral-200 dark:border-white/10">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold" wire:navigate>
                <x-app-logo-icon class="size-7" />
                <span>{{ config('app.name') }}</span>
            </a>

            <div class="flex items-center gap-3">
                @auth
                    <flux:button href="{{ route('dashboard') }}" size="sm" variant="primary" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:button>
                @else
                    <flux:button href="{{ route('login') }}" size="sm" variant="ghost" wire:navigate>
                        {{ __('Log in') }}
                    </flux:button>
                    <flux:button href="{{ route('register') }}" size="sm" variant="primary" wire:navigate>
                        {{ __('Register') }}
                    </flux:button>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-10">
        {{ $slot }}
    </main>

    <footer class="border-t border-neutral-200 py-8 dark:border-white/10">
        <div class="mx-auto max-w-7xl px-6 text-sm text-neutral-500">
            &copy; {{ now()->year }} {{ config('app.name') }}
        </div>
    </footer>

    @fluxScripts
</body>
</html>
