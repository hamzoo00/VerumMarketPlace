<x-layouts::account
    :title="__('Admin sign in')"
    :kicker="__('Staff only')"
    :heading="__('Administrator sign in')"
    :lede="__('This area is restricted. Accounts are created by the engineering team.')"
>
    <form method="POST" action="{{ route('admin.login') }}" class="cform" data-validate>
        @csrf

        <x-verum.form.field
            name="username"
            :label="__('Username')"
            autocomplete="username"
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

        <label class="chk chk--inline">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>{{ __('Keep me signed in') }}</span>
        </label>

        <button class="btn btn--ruby btn--lg btn--block" type="submit">{{ __('Sign in') }}</button>
    </form>

    <p class="authcard__note">
        <x-verum.icon name="lock" />
        {{ __('There is no password reset here. A locked-out administrator is restored from the command line.') }}
    </p>
</x-layouts::account>
