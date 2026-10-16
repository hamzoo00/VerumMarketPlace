@props([
    'title' => null,
    'description' => null,
    'kicker' => null,
    'heading' => null,
    'lede' => null,
    'wide' => false,
])

{{-- Centred shell for sign-in, registration and application screens. --}}
<x-layouts::public :$title :$description robots="noindex, nofollow">
    <div @class(['authwrap', 'authwrap--wide' => $wide])>
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
</x-layouts::public>
