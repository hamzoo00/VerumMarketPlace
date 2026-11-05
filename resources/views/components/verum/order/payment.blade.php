@props(['order'])

{{--
    How the buyer pays: straight to the seller's PayPal. A plain form, not a
    live one — the answer is a redirect to PayPal, which fetch cannot follow.
--}}
@if ($order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT)
    <div class="opay">
        @if ($order->isPayable())
            <p class="mute">
                {{ __('Pay :amount through PayPal. The money goes straight to the seller, and PayPal’s buyer protection covers you.', [
                    'amount' => $order->total()->format(),
                ]) }}
            </p>

            @unless ($order->hasAddress())
                <p class="opay__note">
                    <x-verum.icon name="pin" />
                    {{ __('PayPal will ask where to send it.') }}
                </p>
            @endunless

            <form method="POST" action="{{ route('buyer.orders.pay', $order) }}">
                @csrf
                <button class="btn btn--ruby btn--block" type="submit">
                    <x-verum.icon name="card" />{{ __('Pay with PayPal') }}
                </button>
            </form>
        @else
            <p class="opay__note">
                <x-verum.icon name="clock" />
                {{ __('The seller is still setting up PayPal. You can pay here as soon as they have.') }}
            </p>
        @endif
    </div>
@endif
