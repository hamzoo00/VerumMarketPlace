@props(['order', 'empty' => null])

@php
    use App\Support\Carriers;

    $carrier = Carriers::name($order->tracking_carrier) ?? __('Carrier');
@endphp

{{--
    The parcel's journey, newest first. Every step says where it came
    from — the carrier's scan or the seller's word — because a buyer
    weighs the two differently, and so does a dispute.
--}}
@if ($order->shipmentEvents->isNotEmpty())
    <ol class="shipline">
        @foreach ($order->shipmentEvents as $event)
            <li @class(['shipline__step', 'is-latest' => $loop->first, 'is-'.$event->tone() => $event->tone()])>
                <div class="shipline__head">
                    <b>{{ $event->statusLabel() }}</b>

                    <span @class(['shipline__src', 'is-courier' => $event->fromCourier()])>
                        @if ($event->fromCourier())
                            <x-verum.icon name="globe" />{{ $carrier }}
                        @else
                            <x-verum.icon name="user" />{{ __('Seller') }}
                        @endif
                    </span>
                </div>

                @if ($event->description)
                    <p class="shipline__text">{{ $event->description }}</p>
                @endif

                <p class="shipline__meta">
                    <time datetime="{{ $event->occurred_at->toIso8601String() }}">{{ $event->occurred_at->format('j M Y, H:i') }}</time>
                    @if ($event->location)
                        <span>· {{ $event->location }}</span>
                    @endif
                </p>
            </li>
        @endforeach
    </ol>
@elseif ($empty)
    <p class="mute">{{ $empty }}</p>
@endif
