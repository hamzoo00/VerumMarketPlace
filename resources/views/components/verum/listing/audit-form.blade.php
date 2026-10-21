@props(['listing'])

@php
    $reasons = config('verum_cases.audits.reasons');
@endphp

{{-- "Something looks wrong here." Open to any signed-in buyer; the
     admin decides what, if anything, happens. --}}
<details class="auditbox">
    <summary>
        <x-verum.icon name="shield" />{{ __('Ask us to audit this listing') }}
    </summary>

    @auth('buyer')
        <form method="POST" action="{{ route('listings.audit', $listing) }}" class="auditbox__form">
            @csrf

            <x-verum.form.select
                name="reason"
                :label="__('What is wrong?')"
                :options="$reasons"
                :placeholder="__('Choose…')"
                required
                full
            />

            <x-verum.form.textarea
                name="body"
                :label="__('Tell us what you noticed')"
                maxlength="2000"
                :hint="__('The more specific, the faster we can check it.')"
                required
            />

            <button class="btn btn--ghost btn--sm" type="submit">{{ __('Send to our team') }}</button>
        </form>
    @else
        <p class="auditbox__note">
            {{ __('Sign in as a buyer to report a listing.') }}
        </p>
        <a class="btn btn--ghost btn--sm" href="{{ route('buyer.login') }}" wire:navigate>
            {{ __('Sign in') }}
        </a>
    @endauth
</details>
