{{-- Guests get the sign-in prompt; authenticated users get real links. --}}
<div class="acct">
    <button
        class="ibtn ibtn--hide-sm"
        type="button"
        id="acctBtn"
        aria-expanded="false"
        aria-controls="acctPop"
        aria-label="{{ __('Account') }}"
        data-tip="{{ __('Account') }}"
    >
        <x-verum.icon name="user" />
    </button>

    <div class="acct__pop" id="acctPop" hidden>
        @auth
            <p class="acct__hi">{{ __('Signed in as :name', ['name' => auth()->user()->name]) }}</p>
            <div class="acct__btns">
                <a class="btn btn--ruby btn--sm btn--block" href="{{ route('dashboard') }}" wire:navigate>
                    {{ __('Dashboard') }}
                </a>
            </div>
        @else
            <p class="acct__hi">{{ __('Welcome to Verum') }}</p>
            <p class="acct__note">{{ __('Sign in to save stones, bid on lots, and track your orders.') }}</p>
            <div class="acct__btns">
                <a class="btn btn--ruby btn--sm btn--block" href="{{ route('login') }}" wire:navigate>
                    {{ __('Sign in') }}
                </a>
                <a class="btn btn--ghost btn--sm btn--block" href="{{ route('register') }}" wire:navigate>
                    {{ __('Create account') }}
                </a>
            </div>
        @endauth

        <ul class="acct__links">
            <li>
                <button type="button" data-open-wish>
                    <x-verum.icon name="heart" />{{ __('Saved stones') }} <span data-wish-count2>0</span>
                </button>
            </li>
            <li><a href="{{ route('cart') }}" wire:navigate><x-verum.icon name="bag" />{{ __('Cart & checkout') }}</a></li>
            <li><a href="{{ route('verify') }}" wire:navigate><x-verum.icon name="shield" />{{ __('Verify a report') }}</a></li>
            <li><a href="{{ route('sell') }}" wire:navigate><x-verum.icon name="gem" />{{ __('Sell on Verum') }}</a></li>
        </ul>
    </div>
</div>
