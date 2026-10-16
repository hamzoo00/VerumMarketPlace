@props(['area'])

{{-- Change password for a signed-in buyer or seller. --}}
<x-verum.account.panel :title="__('Change password')" icon="lock" :hint="__('You stay signed in on this device.')">
    @if (session('status'))
        <div class="notice notice--ok" role="status">
            <x-verum.icon name="check" />
            <p>{{ session('status') }}</p>
        </div>
    @endif

    <x-verum.form.errors />

    <form method="POST" action="{{ route('password.change', ['area' => config('verum_routes.'.$area)]) }}" class="cform" data-validate>
        @csrf

        <x-verum.form.field
            name="current_password"
            type="password"
            :label="__('Current password')"
            autocomplete="current-password"
            required
            full
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

        <button class="btn btn--ruby btn--block" type="submit">{{ __('Update password') }}</button>
    </form>
</x-verum.account.panel>
