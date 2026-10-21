@props(['seller'])

@php
    $reasons = config('verum_cases.disputes.reasons');
@endphp

{{-- Raising a problem with a seller. Deliberately low-key: most visits
     are not complaints. --}}
<details class="auditbox">
    <summary>
        <x-verum.icon name="scale" />{{ __('Raise a problem with this seller') }}
    </summary>

    @auth('buyer')
        <form method="POST" action="{{ route('sellers.dispute', $seller) }}" class="auditbox__form">
            @csrf

            <x-verum.form.select
                name="reason"
                :label="__('What happened?')"
                :options="$reasons"
                :placeholder="__('Choose…')"
                required
                full
            />

            <x-verum.form.textarea
                name="body"
                :label="__('Tell us the details')"
                maxlength="2000"
                :hint="__('Dates, what was agreed, and what went wrong.')"
                required
            />

            <button class="btn btn--ghost btn--sm" type="submit">{{ __('Raise a dispute') }}</button>
        </form>
    @else
        <p class="auditbox__note">{{ __('Sign in as a buyer to raise a dispute.') }}</p>
        <a class="btn btn--ghost btn--sm" href="{{ route('buyer.login') }}" wire:navigate>{{ __('Sign in') }}</a>
    @endauth
</details>
