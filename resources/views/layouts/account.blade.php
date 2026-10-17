@props([
    'title' => null,
    'description' => null,
    'kicker' => null,
    'heading' => null,
    'lede' => null,
    'wide' => false,
    'bare' => false,
])

{{-- Centred shell for sign-in, registration and application screens. --}}
@php
    // `bare` renders without the storefront header, ticker, footer and chat —
    // used by the admin sign-in, which is not a customer-facing page.
    $shell = $bare ? 'layouts::blank' : 'layouts::public';
@endphp

<x-dynamic-component :component="$shell" :title="$title" :description="$description" robots="noindex, nofollow">
    <div @class(['authwrap', 'authwrap--wide' => $wide, 'authwrap--bare' => $bare])>
        <div class="authcard" data-reveal>
            @if ($kicker)
                <p class="kicker">{{ $kicker }}</p>
            @endif

            @if ($heading)
                <h1 class="authcard__title">{{ $heading }}</h1>
            @endif

            @if ($lede)
                <p class="authcard__lede">{{ $lede }}</p>
            @endif

            <x-verum.form.errors />

            @if (session('status'))
                <div class="notice notice--ok" role="status">
                    <x-verum.icon name="check" />
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            {{ $slot }}
        </div>

        @isset($aside)
            <div class="authaside" data-reveal style="--d:120">
                {{ $aside }}
            </div>
        @endisset
    </div>
</x-dynamic-component>
