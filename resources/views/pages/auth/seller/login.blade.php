<x-layouts::account
    :title="__('Seller sign in')"
    :kicker="__('Welcome back')"
    :heading="__('Sign in to sell')"
    :lede="__('Manage your listings, lots and orders.')"
>
    <x-verum.auth.switcher active="seller" />

    <x-verum.auth.google-button area="seller" action="in" />

    <form method="POST" action="{{ route('seller.login') }}" class="cform" data-validate>
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

        <div class="cform__row">
            <label class="chk chk--inline">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>{{ __('Keep me signed in') }}</span>
            </label>

            <a class="link" href="{{ route('account.password.request', ['area' => config('verum_routes.seller')]) }}" wire:navigate>
                {{ __('Forgot your password?') }}
            </a>
        </div>

        <button class="btn btn--ruby btn--lg btn--block" type="submit">
            {{ __('Sign in') }}
        </button>
    </form>

    <p class="authcard__alt">
        {{ __('Not selling with us yet?') }}
        <a class="link" href="{{ route('seller.register') }}" wire:navigate>{{ __('Apply to sell') }}</a>
    </p>

    <p class="authcard__note">
        <x-verum.icon name="shield" />
        {{ __('Seller accounts are reviewed by hand before the first listing goes live.') }}
    </p>
</x-layouts::account>
