@props(['order'])

{{--
    The figures as they were agreed, read from the order row and never
    recomputed — a seller may reprice tomorrow, and this has to keep
    saying what was actually charged.
--}}
<dl class="osum">
    <div class="osum__row">
        <dt>{{ __('Items') }}</dt>
        <dd>{{ $order->subtotal()->withLocal() }}</dd>
    </div>

    <div class="osum__row">
        <dt>{{ __('Postage') }}</dt>
        <dd>
            @if ($order->shipping_cents === 0)
                {{ __('Free') }}
            @else
                {{ $order->shipping()->withLocal() }}
            @endif
        </dd>
    </div>

    @if ($order->discount_cents > 0)
        <div class="osum__row osum__row--off">
            <dt>
                {{ __('Discount') }}
                @if ($order->coupon_code)
                    <span class="chip">{{ $order->coupon_code }}</span>
                @endif
            </dt>
            <dd>−{{ $order->discount()->withLocal() }}</dd>
        </div>
    @endif

    <div class="osum__row osum__row--total">
        <dt>{{ __('Total') }}</dt>
        <dd>{{ $order->total()->withLocal() }}</dd>
    </div>
</dl>
