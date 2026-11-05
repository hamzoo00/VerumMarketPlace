@props(['order'])

@php
    use App\Support\Carriers;

    $url = Carriers::trackingUrl($order->tracking_carrier, $order->tracking_number);
@endphp

{{-- The carrier's own page does the tracking; we only hold the number. --}}
@if ($order->tracking_number)
    <div class="otrack__num">
        <x-verum.icon name="globe" />
        <span>{{ Carriers::name($order->tracking_carrier) }} · <b>{{ $order->tracking_number }}</b></span>

        @if ($url)
            <a class="link" href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                {{ __('Track parcel') }}<x-verum.icon name="arrow-ur" />
            </a>
        @endif
    </div>
@endif
