@php
    // As above: Fortify's bare /reset-password has no {area}.
    $area = $area ?? \App\Support\RouteSegments::get('buyer');
@endphp

<x-layouts::account
    :title="__('Choose a new password')"
    :kicker="$area === 'seller' ? __('Seller account') : __('Buyer account')"
    :heading="__('Choose a new password')"
    :lede="__('Pick something you have not used here before.')"
>
    <form method="POST" action="{{ route('account.password.update', ['area' => $area]) }}" class="cform" data-validate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-verum.form.field
            name="email"
            type="email"
            :label="__('Email')"
            :value="$email"
            autocomplete="email"
            required
            full
            readonly
        />

        <x-verum.form.field
            name="password"
            type="password"
            :label="__('New password')"
            autocomplete="new-password"
            rule="password"
            required
            full
        />

        <x-verum.form.field
            name="password_confirmation"
            type="password"
            :label="__('Confirm new password')"
            autocomplete="new-password"
            confirms="password"
            required
            full
        />

        <button class="btn btn--ruby btn--lg btn--block" type="submit">
            {{ __('Set new password') }}
        </button>
    </form>
</x-layouts::account>
