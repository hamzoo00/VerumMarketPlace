@php
    $isSeller = $area === 'seller';
@endphp

<x-layouts::account
    :title="__('Reset your password')"
    :kicker="$isSeller ? __('Seller account') : __('Buyer account')"
    :heading="__('Forgot your password?')"
    :lede="__('Give us the email on your account and we will send a link to set a new password.')"
>
    <x-verum.auth.switcher :active="$area" />

    <form method="POST" action="{{ route('password.email', ['area' => $area]) }}" class="cform" data-validate>
        @csrf

        <x-verum.form.field
            name="email"
            type="email"
            :label="__('Email')"
            autocomplete="email"
            required
            full
        />

        <button class="btn btn--ruby btn--lg btn--block" type="submit">
            {{ __('Email me a reset link') }}
        </button>

        <p class="cform__note">
            <x-verum.icon name="info" />
            {{ __('You can request :max links in a row; after that there is a short wait.', ['max' => $throttle['max']]) }}
        </p>
    </form>

    <p class="authcard__alt">
        {{ __('Remembered it?') }}
        <a class="link" href="{{ route($area.'.login') }}" wire:navigate>{{ __('Sign in') }}</a>
    </p>
</x-layouts::account>
