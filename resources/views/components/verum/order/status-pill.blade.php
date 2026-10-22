@props(['order'])

@php
    use App\Models\Order;

    $tone = match ($order->status) {
        Order::STATUS_DELIVERED => 'ok',
        Order::STATUS_DISPATCHED => 'on',
        Order::STATUS_CANCELLED => 'off',
        Order::STATUS_AWAITING_PAYMENT => 'warn',
        default => null,
    };
@endphp

<span @class(['chip', 'chip--'.$tone => $tone])>{{ $order->statusLabel() }}</span>
