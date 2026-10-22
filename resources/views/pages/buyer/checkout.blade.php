@php
    use App\Models\Money;

    $price = $listing->price();
    $cheapest = $quote->cheapest();
@endphp

<x-layouts::account-area
    :title="__('Checkout')"
    :heading="__('Checkout')"
    :lede="__('Where should we send it?')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <div class="cogrid">
        <x-verum.account.panel :title="__('Delivery address')" icon="box">
            {{--
                Only the address and the buyer's choices post from here.
                Every figure is recomputed server-side by PlaceOrder from
                the seller's own rows, so nothing in this form can change
                what is charged.
            --}}
            <form method="POST" action="{{ route('listings.checkout.store', $listing) }}" class="cform" data-validate>
                @csrf

                <x-verum.form.errors />

                <div class="fgrid">
                    <x-verum.form.field
                        name="name"
                        :label="__('Full name')"
                        :value="$buyer->name"
                        autocomplete="name"
                        required
                        full
                    />

                    <x-verum.form.field
                        name="line1"
                        :label="__('Address')"
                        autocomplete="address-line1"
                        required
                        full
                    />

                    <x-verum.form.field
                        name="line2"
                        :label="__('Apartment, suite (optional)')"
                        autocomplete="address-line2"
                        full
                    />

                    <x-verum.form.field name="city" :label="__('City')" autocomplete="address-level2" required />
                    <x-verum.form.field name="region" :label="__('State or region')" autocomplete="address-level1" />
                    <x-verum.form.field name="postcode" :label="__('Postcode')" autocomplete="postal-code" />

                    <x-verum.form.country :selected="$buyer->country" required />

                    <x-verum.form.field name="phone" :label="__('Phone (optional)')" autocomplete="tel" />
                </div>

                @if ($quote->options->isNotEmpty())
                    <fieldset class="fgrp">
                        <legend class="lbl">{{ __('Postage') }}</legend>

                        <div class="chklist">
                            @foreach ($quote->options as $rate)
                                <label class="chk">
                                    <input
                                        type="radio"
                                        name="shipping_service"
                                        value="{{ $rate->service }}"
                                        @checked(old('shipping_service', $cheapest?->service) === $rate->service)
                                    >
                                    <span>
                                        {{ $rate->serviceLabel() }}
                                        <small class="mute">{{ $rate->destinationLabel() }}</small>
                                    </span>
                                    <b>{{ $rate->isFree() ? __('Free') : $rate->price($listing->currency)->format() }}</b>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <div class="fgrid">
                    <x-verum.form.field
                        name="coupon_code"
                        :label="__('Discount code (optional)')"
                        :hint="__('Applied against this seller\'s own campaigns.')"
                    />
                </div>

                <x-verum.form.textarea
                    name="note"
                    :label="__('Note for the seller (optional)')"
                    rows="3"
                    maxlength="500"
                />

                <button class="btn btn--ruby btn--lg btn--block" type="submit">
                    {{ __('Place order') }}
                </button>

                <p class="cform__fine">
                    {{ __('Nothing is charged yet. The seller confirms payment, then posts your stone.') }}
                </p>
            </form>
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Your order')" icon="gem">
            <div class="copreview">
                @if ($listing->imageUrls() !== [])
                    <img class="copreview__shot" src="{{ $listing->imageUrls()[0] }}" alt="" loading="lazy" decoding="async">
                @endif

                <div class="copreview__what">
                    <b>{{ $listing->title }}</b>
                    <small class="mute">{{ $listing->seller?->name }}</small>
                </div>

                <span class="copreview__price">{{ $price?->withLocal() }}</span>
            </div>

            <p class="copreview__ship">
                <x-verum.icon name="globe" />
                {{ $quote->summary() }} · {{ $quote->handlingLabel() }}
            </p>

            @if ($quote->note)
                <p class="mute">{{ $quote->note }}</p>
            @endif
        </x-verum.account.panel>
    </div>
</x-layouts::account-area>
