@props([
    'area' => 'buyer',
    'action' => 'in',
])

@php
    // Not wired yet: routes and OAuth credentials come later. The button is
    // disabled rather than linked, so it cannot 404 in the meantime.
    $configured = filled(config("services.google.client_id"));
    $label = $action === 'up'
        ? __('Sign up with Google')
        : __('Continue with Google');
@endphp

<div class="oauth">
    @if ($configured)
        <a class="oauth__btn" href="{{ route('oauth.redirect', ['provider' => 'google', 'area' => $area]) }}">
            <x-verum.auth.google-mark />
            <span>{{ $label }}</span>
        </a>
    @else
        <button class="oauth__btn" type="button" disabled aria-describedby="oauth-note-{{ $area }}">
            <x-verum.auth.google-mark />
            <span>{{ $label }}</span>
        </button>
        <p class="oauth__note" id="oauth-note-{{ $area }}">{{ __('Google sign-in is not connected yet.') }}</p>
    @endif

    <p class="oauth__or"><span>{{ __('or continue with email') }}</span></p>
</div>
