@props(['listing'])

@php
    // A seller must not buy from their own storefront. Resolved from the
    // session, never from anything the page sends back.
    $isOwnListing = auth('seller')->check()
        && auth('seller')->user()?->storefront?->id === $listing->seller_id;

    $sold = in_array($listing->status, [
        \App\Models\Listing::STATUS_SOLD,
        \App\Models\Listing::STATUS_ENDED,
    ], true);

    $quote = $listing->shipping();
@endphp

{{-- Fixed-price listings only; an auction is bought by bidding. --}}
<div class="buyacts" data-reveal>
    @if ($sold)
        <p class="buyacts__note">
            <x-verum.icon name="info" />
            {{ $listing->status === \App\Models\Listing::STATUS_SOLD ? __('This item is sold.') : __('This listing has ended.') }}
        </p>
    @elseif ($isOwnListing)
        <p class="buyacts__note">
            <x-verum.icon name="info" />
            {{ __('This is your own listing.') }}
        </p>

        <a class="btn btn--ghost btn--block" href="{{ route('seller.listings.edit', $listing) }}" wire:navigate>
            {{ __('Edit listing') }}
        </a>
    @else
        <div class="buyacts__btns">
            <a class="btn btn--ruby btn--lg" href="{{ route('checkout') }}" data-buy-now="{{ $listing->slug }}">
                <x-verum.icon name="bag" />{{ __('Buy it now') }}
            </a>

            <button class="btn btn--ghost btn--lg" type="button" data-add="{{ $listing->slug }}">
                {{ __('Add to cart') }}
            </button>
        </div>

        <x-verum.listing.offer-form :$listing />

        @if (! $quote->isConfigured())
            <p class="buyacts__warn">
                <x-verum.icon name="info" />
                {{ __('Postage is not published for this item — ask the seller before paying.') }}
            </p>
        @endif
    @endif
</div>
