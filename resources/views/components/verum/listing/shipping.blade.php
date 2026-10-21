@props(['listing'])

@php
    $quote = $listing->shipping();
@endphp

{{-- What delivery costs, shown before a buyer commits to anything. --}}
<section class="lship" aria-labelledby="ship-h">
    <h2 class="lship__h lbl" id="ship-h">{{ __('Delivery') }}</h2>

    @if (! $quote->isConfigured())
        <p class="lship__none">
            <x-verum.icon name="info" />
            {{ __('This seller has not published postage prices. Ask before you buy.') }}
        </p>
    @else
        <p class="lship__sum">
            <x-verum.icon name="box" />
            <strong>{{ $quote->summary() }}</strong>
        </p>

        @foreach ($quote->byDestination() as $destination => $rates)
            <p class="lship__zone lbl">
                {{ config("verum_shipping.destinations.{$destination}", $destination) }}
            </p>

            <ul class="lship__opts">
                @foreach ($rates as $rate)
                    <li>
                        <span class="lship__svc">{{ $rate->serviceLabel() }}</span>
                        <span class="lship__when">{{ $rate->transitLabel() }}</span>
                        <span class="lship__cost">
                            {{ $rate->isFree() ? __('Free') : $rate->price($listing->currency)->format() }}
                        </span>

                        @if ($rate->tracked || $rate->insured)
                            <span class="lship__tags">
                                @if ($rate->tracked)<span class="disc">{{ __('Tracked') }}</span>@endif
                                @if ($rate->insured)<span class="disc">{{ __('Insured') }}</span>@endif
                            </span>
                        @endif

                        @if ($rate->note)
                            <small class="lship__note">{{ $rate->note }}</small>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach

        <p class="lship__meta">{{ $quote->handlingLabel() }}</p>

        @if ($quote->freeOver)
            <p class="lship__meta">
                {{ __('Free postage on orders over :amount.', ['amount' => $quote->freeOver->format()]) }}
            </p>
        @endif

        @if ($quote->note)
            <p class="lship__seller">{{ $quote->note }}</p>
        @endif
    @endif
</section>
