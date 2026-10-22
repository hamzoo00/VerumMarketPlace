@props([
    'offer',
    'side' => 'seller',
])

@php
    $live = $offer->isLive();
    $mine = $offer->from === $side;
    $myTurn = $live && ! $mine;
    $listing = $offer->listing;
@endphp

{{--
    One negotiation row, from either side. `side` decides whose turn it
    is and therefore which controls appear — the server re-checks both.
--}}
<article @class(['offerrow', 'is-live' => $live]) data-offer="{{ $offer->id }}">
    <div class="offerrow__item">
        @if ($listing)
            <a class="link" href="{{ route('listings.show', $listing) }}" wire:navigate>{{ $listing->title }}</a>
            <small>{{ __('Asking :price', ['price' => $listing->price()?->withLocal()]) }}</small>
        @else
            <span class="mute">{{ __('Listing removed') }}</span>
        @endif
    </div>

    <div class="offerrow__who">
        <span>{{ $side === 'seller' ? $offer->buyer?->name : $offer->seller?->name }}</span>
        <small>{{ $mine ? __('You offered') : __('They offered') }}</small>
    </div>

    <strong class="offerrow__amount">{{ $offer->amount()->withLocal() }}</strong>

    <div class="offerrow__state">
        <span @class(['chip', 'chip--ok' => $offer->status === \App\Models\Offer::STATUS_ACCEPTED, 'chip--live' => $live])>
            {{ $offer->statusLabel() }}
        </span>

        @if ($live)
            <small>{{ __('Expires :when', ['when' => $offer->expires_at->diffForHumans()]) }}</small>
        @endif
    </div>

    @if ($offer->message)
        <p class="offerrow__msg">{{ $offer->message }}</p>
    @endif

    @if ($myTurn)
        <div class="offerrow__acts">
            {{ $actions }}
        </div>
    @elseif ($live && $mine && $side === 'buyer')
        <form class="offerrow__acts" method="POST" action="{{ route('buyer.offers.respond', $offer) }}">
            @csrf
            <input type="hidden" name="decision" value="withdraw">
            <button class="btn btn--ghost btn--sm" type="submit">{{ __('Withdraw') }}</button>
        </form>
    @endif
</article>
