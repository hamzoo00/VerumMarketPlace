@props(['listing'])

@php
    use App\Support\OfferRules;

    $open = OfferRules::isOpen($listing);
    $buyer = auth('buyer')->user();

    // One live offer per buyer per listing, so the form knows whether
    // there is already something waiting.
    $existing = $open && $buyer
        ? $listing->offers()->where('buyer_id', $buyer->id)->live()->latest('round')->first()
        : null;

    $minimum = $open ? new \App\Models\Money(OfferRules::minimumOffer($listing), $listing->currency) : null;
@endphp

@if ($open)
    <div class="makeoffer">
        @if ($existing)
            <p class="makeoffer__state">
                <x-verum.icon name="scale" />
                {{ $existing->from === \App\Models\Offer::FROM_BUYER
                    ? __('Your offer of :amount is with the seller.', ['amount' => $existing->amount()->format()])
                    : __('The seller countered at :amount.', ['amount' => $existing->amount()->format()]) }}
            </p>

            <a class="btn btn--ghost btn--sm" href="{{ route('buyer.offers') }}" wire:navigate>
                {{ __('See your offers') }}
            </a>
        @elseif ($buyer)
            <form method="POST" action="{{ route('listings.offers.store', $listing) }}" class="makeoffer__form">
                @csrf

                <label class="lbl" for="offerAmount">{{ __('Make an offer') }}</label>

                <div class="makeoffer__row">
                    <x-verum.form.field
                        name="amount"
                        id="offerAmount"
                        :label="__('Your price')"
                        prefix="$"
                        type="number"
                        step="0.01"
                        :min="$minimum->amount()"
                        required
                    />

                    <button class="btn btn--ruby" type="submit">{{ __('Send offer') }}</button>
                </div>

                <x-verum.form.textarea
                    name="message"
                    :label="__('Message (optional)')"
                    maxlength="500"
                />

                <p class="makeoffer__hint">
                    {{ __('Lowest considered: :amount. The seller has :hours hours to reply.', [
                        'amount' => $minimum->format(),
                        'hours' => config('verum_offers.offers.expires_after_hours'),
                    ]) }}
                </p>
            </form>
        @else
            <p class="makeoffer__state">
                <x-verum.icon name="scale" />
                {{ __('This seller takes offers.') }}
            </p>

            <a class="btn btn--ghost btn--sm" href="{{ route('buyer.login') }}" wire:navigate>
                {{ __('Sign in to make an offer') }}
            </a>
        @endif
    </div>
@endif
