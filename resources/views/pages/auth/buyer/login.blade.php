<x-layouts::account
    :title="__('Buyer sign in')"
    :kicker="__('Welcome back')"
    :heading="__('Sign in to buy')"
    :lede="__('Track your bids, saved stones and orders.')"
>
    <x-verum.auth.switcher active="buyer" />

    <form method="POST" action="{{ route('buyer.login') }}" class="cform" data-validate>
        @csrf

        <x-verum.form.field
            name="email"
            type="email"
            :label="__('Email')"
            autocomplete="email"
            required
            full
        />

        <x-verum.form.field
            name="password"
            type="password"
            :label="__('Password')"
            autocomplete="current-password"
            required
            full
        />

        <p class="cform__forgot">
            <a class="link" href="{{ route('password.request', ['area' => config('verum_routes.buyer')]) }}" wire:navigate>
                {{ __('Forgot your password?') }}
            </a>
        </p>

        <label class="chk chk--inline">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>{{ __('Keep me signed in') }}</span>
        </label>

        <button class="btn btn--ruby btn--lg btn--block" type="submit">
            {{ __('Sign in') }}
        </button>
    </form>

    <p class="authcard__alt">
        {{ __("Don't have an account?") }}
        <a class="link" href="{{ route('buyer.register') }}" wire:navigate>{{ __('Create one') }}</a>
    </p>
</x-layouts::account>
