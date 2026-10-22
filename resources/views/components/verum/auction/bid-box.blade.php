@props(['lot'])

@php
    $current = \App\Models\Money::fromCents($lot->current_bid_cents);
    $next = \App\Models\Money::fromCents($lot->current_bid_cents + $lot->bid_increment_cents);
    $low = \App\Models\Money::fromCents($lot->estimate_low_cents);
    $high = \App\Models\Money::fromCents($lot->estimate_high_cents);
    $ended = $lot->hasEnded();
@endphp

{{-- Bidding is display-only until the auction backend and Reverb are wired. --}}
<div @class(['bidbox', 'is-closed' => $ended]) data-reveal data-lot="{{ $lot->stone->slug }}">
    <div class="bidbox__row">
        <div>
            <span class="lbl">{{ $ended ? __('Final bid') : __('Current bid') }}</span>
            <strong class="bidbox__cur" data-cur="{{ $lot->stone->slug }}">{{ $current->withLocal() }}</strong>
            <small>{{ trans_choice(':count bid|:count bids', $lot->bids_count) }}</small>
        </div>
        <div class="bidbox__clock">
            <span class="lbl">{{ $ended ? __('Closed') : __('Time left') }}</span>
            <x-verum.countdown :ends-at="$lot->ends_at" :slug="$lot->stone->slug" />
        </div>
    </div>

    <p class="bidbox__est">
        {{ __('Estimate :low – :high', ['low' => $low->format(), 'high' => $high->format()]) }}
    </p>

    @unless ($ended)
        <button class="btn btn--ruby btn--lg btn--block" type="button" data-bid="{{ $lot->stone->slug }}">
            {{ __('Place bid from :amount', ['amount' => $next->format()]) }}
        </button>
        <p class="bidbox__note">
            <x-verum.icon name="info" />
            {{ __('A bid in the final two minutes extends the lot, so a late bid never steals it.') }}
        </p>
    @else
        <p class="bidbox__note">{{ __('This lot has closed.') }}</p>
    @endunless
</div>
