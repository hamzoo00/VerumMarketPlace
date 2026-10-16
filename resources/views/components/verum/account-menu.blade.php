{{-- Buyers and sellers are separate accounts, each with its own area. --}}
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
        @auth('buyer')
            @php($buyer = auth('buyer')->user())
            <p class="acct__hi">{{ __('Signed in as :name', ['name' => $buyer->name]) }}</p>
            <p class="acct__note">{{ __('Buyer account') }}</p>
            <div class="acct__btns">
                <a class="btn btn--ruby btn--sm btn--block" href="{{ route('buyer.dashboard') }}" wire:navigate>
                    {{ __('My account') }}
                </a>
            </div>
            <ul class="acct__links">
                <li><a href="{{ route('buyer.saved') }}" wire:navigate><x-verum.icon name="heart" />{{ __('Saved stones') }}</a></li>
                <li><a href="{{ route('buyer.bids') }}" wire:navigate><x-verum.icon name="gavel" />{{ __('My bids') }}</a></li>
                <li><a href="{{ route('buyer.orders') }}" wire:navigate><x-verum.icon name="box" />{{ __('Orders') }}</a></li>
            </ul>
            <form method="POST" action="{{ route('buyer.logout') }}" class="acct__out">
                @csrf
                <button class="link" type="submit">{{ __('Sign out') }}</button>
            </form>
        @elseauth('seller')
            @php($account = auth('seller')->user())
            <p class="acct__hi">{{ __('Signed in as :name', ['name' => $account->name]) }}</p>
            <p class="acct__note">
                {{ $account->isApproved() ? __('Verified seller') : __('Application under review') }}
            </p>
            <div class="acct__btns">
                <a
                    class="btn btn--ruby btn--sm btn--block"
                    href="{{ $account->isApproved() ? route('seller.dashboard') : route('seller.status') }}"
                    wire:navigate
                >
                    {{ $account->isApproved() ? __('Seller dashboard') : __('Application status') }}
                </a>
            </div>
            <form method="POST" action="{{ route('seller.logout') }}" class="acct__out">
                @csrf
                <button class="link" type="submit">{{ __('Sign out') }}</button>
            </form>
        @else
            <p class="acct__hi">{{ __('Welcome to :app', ['app' => config('app.name')]) }}</p>
            <p class="acct__note">{{ __('Buyer and seller accounts are separate.') }}</p>
            <div class="acct__btns">
                <a class="btn btn--ruby btn--sm btn--block" href="{{ route('buyer.login') }}" wire:navigate>
                    {{ __('Sign in to buy') }}
                </a>
                <a class="btn btn--ghost btn--sm btn--block" href="{{ route('seller.login') }}" wire:navigate>
                    {{ __('Sign in to sell') }}
                </a>
            </div>
            <ul class="acct__links">
                <li><a href="{{ route('buyer.register') }}" wire:navigate><x-verum.icon name="user" />{{ __('Create a buyer account') }}</a></li>
                <li><a href="{{ route('seller.register') }}" wire:navigate><x-verum.icon name="gem" />{{ __('Apply to sell') }}</a></li>
                <li><a href="{{ route('verify') }}" wire:navigate><x-verum.icon name="shield" />{{ __('Verify a report') }}</a></li>
            </ul>
        @endauth
    </div>
</div>
