@props(['order'])

@php
    use App\Support\Carriers;
    use App\Support\ShipmentStatuses;
    use Illuminate\Support\Facades\URL;

    $url = $order->trackingUrl();
    $simulated = config('verum_tracking.driver') === 'fake';
    $live = $order->tracking_registered_at !== null;
    // Refused by the tracking API: no carrier recognised the number.
    $unwatched = ! $live && $order->tracking_synced_at !== null;
@endphp

{{-- The carrier's own page is the full story; the steps below are what
     reached us from it and from the seller. --}}
@if ($order->tracking_number)
    <div class="otrack__num">
        <x-verum.icon name="globe" />
        <span>{{ Carriers::name($order->tracking_carrier) }} · <b class="mono">{{ $order->tracking_number }}</b></span>

        @if ($url)
            <a class="link" href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                {{ __('Track parcel') }}<x-verum.icon name="arrow-ur" />
            </a>
        @endif
    </div>

    @if ($order->isInTransit())
        <p class="otrack__state">
            @if ($order->tracking_status)
                <span @class(['chip', 'chip--'.ShipmentStatuses::tone($order->tracking_status) => ShipmentStatuses::tone($order->tracking_status)])>
                    {{ ShipmentStatuses::label($order->tracking_status) }}
                </span>
            @endif

            @if ($live)
                <span class="mute">
                    {{ $order->tracking_synced_at
                        ? __('Live from the carrier · checked :when', ['when' => $order->tracking_synced_at->diffForHumans()])
                        : __('Live from the carrier · waiting for its first scan') }}
                </span>
            @elseif ($unwatched)
                <span class="mute">{{ __('The carrier’s scans cannot be read for this number — follow it on their page.') }}</span>
            @endif
        </p>
    @endif

    @if ($simulated && $order->isInTransit())
        {{-- Test mode only: the local stand-in for the courier. --}}
        <a class="otrack__sim link" href="{{ URL::signedRoute('tracking.simulator', $order) }}" target="_blank" rel="noopener">
            <x-verum.icon name="spark" />{{ __('Courier simulator (test mode)') }}
        </a>
    @endif
@endif
