@props([
    'listing',
    'open' => false,
])

@php
    use App\Support\AuctionRules;

    $minimum = new \App\Models\Money(AuctionRules::minimumBid($listing), $listing->currency);
    $increment = new \App\Models\Money(AuctionRules::increment($listing), $listing->currency);
    $reserve = AuctionRules::reserveLabel($listing);
@endphp

<div @class(['bidbox', 'is-closed' => ! $open]) data-reveal data-lot="{{ $listing->slug }}">
    <div class="bidbox__row">
        <div>
            <span class="lbl">
                {{ $listing->bids_count > 0 ? __('Current bid') : __('Opening bid') }}
            </span>
            <strong class="bidbox__cur">{{ $listing->currentBid()?->withLocal() }}</strong>
            <small>{{ trans_choice(':count bid|:count bids', $listing->bids_count) }}</small>
        </div>

        <div class="bidbox__clock">
            <span class="lbl">{{ $open ? __('Time left') : __('Closed') }}</span>
            <x-verum.countdown :ends-at="$listing->auction_ends_at" :slug="$listing->slug" />
        </div>
    </div>

    @if ($reserve)
        <p class="bidbox__est">
            <x-verum.icon name="lock" />{{ $reserve }}
        </p>
    @endif

    @if ($open)
        <p class="bidbox__est">
            {{ __('Next bid :amount or more · increments of :step', [
                'amount' => $minimum->format(),
                'step' => $increment->format(),
            ]) }}
        </p>

        @auth('buyer')
            {{-- PlaceBid re-checks every rule with the row locked; this
                 form only carries the amount. --}}
            <form method="POST" action="{{ route('listings.bids.store', $listing) }}" class="bidform">
                @csrf

                <label class="sr-only" for="bidAmount">{{ __('Your bid') }}</label>

                <div class="bidform__row">
                    <x-verum.form.field
                        name="amount"
                        id="bidAmount"
                        :label="__('Your bid')"
                        prefix="$"
                        type="number"
                        step="0.01"
                        :min="$minimum->amount()"
                        :value="old('amount', $minimum->amount())"
                        required
                    />

                    <button class="btn btn--ruby btn--lg" type="submit" @disabled(! $open)>
                        {{ __('Place bid') }}
                    </button>
                </div>

                <p class="bidbox__note">
                    <x-verum.icon name="info" />
                    {{ __('Minimum :amount. Bids are binding.', ['amount' => $minimum->format()]) }}
                </p>
            </form>
        @else
            <a class="btn btn--ruby btn--lg btn--block" href="{{ route('buyer.login') }}" wire:navigate>
                {{ __('Sign in to bid') }}
            </a>
        @endauth

        @if ($listing->extensions_count > 0)
            <p class="bidbox__note">
                <x-verum.icon name="clock" />
                {{ trans_choice(
                    'This lot was extended once by a late bid.|This lot has been extended :count times by late bids.',
                    $listing->extensions_count,
                    ['count' => $listing->extensions_count],
                ) }}
            </p>
        @endif
    @else
        <p class="bidbox__note">
            {{ AuctionRules::didSell($listing)
                ? __('This lot sold.')
                : __('This lot closed without meeting its reserve.') }}
        </p>
    @endif
</div>
