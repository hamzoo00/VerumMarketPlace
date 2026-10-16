<x-layouts::public
    :title="__('Application status')"
    robots="noindex, nofollow"
>
    <div class="wrap authwrap">
        <div class="authcard authcard--status" data-reveal>
            @if ($application === null)
                <p class="kicker">{{ __('No application') }}</p>
                <h1 class="authcard__title">{{ __('Nothing to review yet') }}</h1>
                <p class="authcard__lede">
                    {{ __('We have no application on file for this account. Start one and we will review it by hand.') }}
                </p>
                <a class="btn btn--ruby btn--lg" href="{{ route('sell') }}" wire:navigate>
                    {{ __('How selling works') }}
                </a>
            @else
                @if ($application->isRejected())
                    <p class="kicker">{{ __('Decision') }}</p>
                    <h1 class="authcard__title">{{ __('Not approved this time') }}</h1>
                    <p class="authcard__lede">
                        {{ $application->decision_note ?? __('We could not approve this application. You are welcome to apply again with more detail.') }}
                    </p>
                @else
                    <svg class="okcheck okcheck--lg" viewBox="0 0 80 80" aria-hidden="true">
                        <circle cx="40" cy="40" r="36" />
                        <path d="M24 41l11 11 21-23" />
                    </svg>
                    <p class="kicker">{{ __('Application received') }}</p>
                    <h1 class="authcard__title">{{ __('Thank you, :name.', ['name' => $account->name]) }}</h1>
                    <p class="authcard__lede">
                        {!! __('Reference <b>:reference</b>. We will email <b>:email</b> within 3 business days, approved or not.', [
                            'reference' => e($application->reference),
                            'email' => e($account->email),
                        ]) !!}
                    </p>
                @endif

                <x-verum.seller.application-tracker :$application />

                <div class="sellok__btns">
                    <a class="btn btn--ruby" href="{{ route('sellers.index') }}" wire:navigate>
                        {{ __('Preview a storefront') }}
                        <span class="arr" aria-hidden="true"><svg><use href="#i-arrow" /></svg><svg><use href="#i-arrow" /></svg></span>
                    </a>
                    <form method="POST" action="{{ route('seller.logout') }}">
                        @csrf
                        <button class="btn btn--ghost" type="submit">{{ __('Sign out') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-layouts::public>
