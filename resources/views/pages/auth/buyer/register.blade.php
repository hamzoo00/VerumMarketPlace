<x-layouts::account
    :title="__('Create a buyer account')"
    :kicker="__('For buyers')"
    :heading="__('Create your account')"
    :lede="__('No approval needed. Start bidding straight away.')"
>
    <x-verum.auth.switcher active="buyer" />

    <form method="POST" action="{{ route('buyer.register') }}" class="cform" data-validate>
        @csrf

        <div class="cform__grid">
            <x-verum.form.field name="name" :label="__('Full name')" autocomplete="name" required full />
            <x-verum.form.field name="email" type="email" :label="__('Email')" autocomplete="email" required full />
            <x-verum.form.field name="country" :label="__('Country (optional)')" autocomplete="country-name" />
            <x-verum.form.field
                name="password"
                type="password"
                :label="__('Password')"
                autocomplete="new-password"
                rule="password"
                required
            />
            <x-verum.form.field
                name="password_confirmation"
                type="password"
                :label="__('Confirm password')"
                autocomplete="new-password"
                confirms="password"
                required
                full
            />
        </div>

        <button class="btn btn--ruby btn--lg btn--block" type="submit">
            {{ __('Create account') }}
        </button>
    </form>

    <p class="authcard__alt">
        {{ __('Already have an account?') }}
        <a class="link" href="{{ route('buyer.login') }}" wire:navigate>{{ __('Sign in') }}</a>
    </p>
</x-layouts::account>
