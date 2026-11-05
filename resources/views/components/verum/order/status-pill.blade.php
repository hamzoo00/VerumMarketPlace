@props(['order'])

@php
    use App\Models\Order;
    use App\Support\ShipmentStatuses;

    $tone = match ($order->status) {
        Order::STATUS_DELIVERED => 'ok',
        Order::STATUS_DISPATCHED => 'on',
        Order::STATUS_CANCELLED => 'off',
        Order::STATUS_AWAITING_PAYMENT => 'warn',
        default => null,
    };

    // On the road, where it is matters more than that it left: "at customs"
    // beside "Dispatched". Read off the order row, so a list stays one query.
    $parcel = $order->isInTransit() && ! in_array($order->tracking_status, [null, 'posted'], true)
        ? $order->tracking_status
        : null;
@endphp

<span @class(['chip', 'chip--'.$tone => $tone])>{{ $order->statusLabel() }}</span>
@if ($parcel)
    <span @class(['chip', 'chip--'.ShipmentStatuses::tone($parcel) => ShipmentStatuses::tone($parcel)])>{{ ShipmentStatuses::label($parcel) }}</span>
@endif
