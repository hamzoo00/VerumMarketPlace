@php
    use App\Models\Order;
@endphp

<x-layouts::seller
    :account="$account"
    :title="$order->reference"
    :heading="$order->reference"
    :lede="__('Placed :when by :who', [
        'when' => $order->created_at?->format('j M Y'),
        'who' => $order->buyer?->name,
    ])"
>
    <a class="link" href="{{ route('seller.orders') }}" wire:navigate>{{ __('← All sales') }}</a>

    {{-- Re-read when the buyer pays: the notification lands on this channel. --}}
    <div class="ogrid" data-live-region="order">
        <x-verum.account.panel :title="__('Order')" icon="gem">
            <x-verum.order.lines :$order />
            <x-verum.order.summary :$order />

            @if ($order->buyer_note)
                <p class="onote">
                    <x-verum.icon name="info" />
                    {{ $order->buyer_note }}
                </p>
            @endif
        </x-verum.account.panel>

        <div class="ogrid__side">
            <x-verum.account.panel :title="__('Next step')" icon="box">
                <x-verum.order.status-pill :$order />

                {{-- One action at a time, in the order the real work
                     happens. AdvanceOrder rejects anything out of turn,
                     so this only ever offers what is actually allowed. --}}
                @if ($order->status === Order::STATUS_AWAITING_PAYMENT)
                    {{-- Paid only when PayPal says so; there is no button for it. --}}
                    @if ($order->seller?->canReceivePayments())
                        <p class="mute">{{ __('Waiting for the buyer to pay through PayPal. This updates by itself when they do.') }}</p>
                    @else
                        <p class="opay__note">
                            <x-verum.icon name="card" />
                            {{ __('Buyers pay straight into your PayPal. Connect it so this buyer can pay.') }}
                        </p>
                        <a class="btn btn--ruby btn--block" href="{{ route('seller.payments') }}" wire:navigate>
                            {{ __('Connect PayPal') }}
                        </a>
                    @endif
                @elseif ($order->status === Order::STATUS_PAID)
                    <p class="mute">{{ __('Post the stone, then add the tracking details. PayPal gets the number too, which protects you if the buyer says it never came.') }}</p>

                    <form method="POST" action="{{ route('seller.orders.update', $order) }}" class="cform" data-validate>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="dispatched">

                        <div class="fgrid">
                            <x-verum.form.select
                                name="tracking_carrier"
                                :label="__('Carrier')"
                                :options="\App\Support\Carriers::options()"
                                :placeholder="__('Choose…')"
                            />
                            <x-verum.form.field
                                name="tracking_number"
                                :label="__('Tracking number')"
                                maxlength="40"
                                :pattern="\App\Support\Carriers::trackingPattern()"
                                :data-pattern-message="__('Letters, digits and dashes, as printed on the receipt.')"
                            />
                        </div>

                        <button class="btn btn--ruby btn--block" type="submit">{{ __('Mark as dispatched') }}</button>
                    </form>
                @elseif ($order->status === Order::STATUS_DISPATCHED)
                    <x-verum.order.tracking :$order />

                    <form method="POST" action="{{ route('seller.orders.update', $order) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="delivered">
                        <button class="btn btn--ghost btn--block" type="submit">{{ __('Mark as delivered') }}</button>
                    </form>
                @else
                    <p class="mute">{{ __('Nothing left to do on this one.') }}</p>
                    <x-verum.order.tracking :$order />
                @endif

                @if ($order->isCancellable())
                    {{-- Cancelling puts the stone back on sale, so it is
                         kept apart from the main action. --}}
                    <form method="POST" action="{{ route('seller.orders.update', $order) }}" class="ocancel">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="cancelled">

                        <x-verum.form.field name="reason" :label="__('Reason (optional)')" full />

                        <button class="link" type="submit">{{ __('Cancel this order') }}</button>
                        <small class="mute">
                            {{ $order->status === Order::STATUS_PAID
                                ? __('The buyer is refunded through PayPal and the listing goes back on sale.')
                                : __('The listing goes back on sale.') }}
                        </small>
                    </form>
                @endif
            </x-verum.account.panel>

            <x-verum.account.panel :title="__('Post to')" icon="pin">
                @if ($order->hasAddress())
                    <address class="oaddr">{{ $order->addressLines() }}</address>
                @else
                    <p class="mute">{{ __('The buyer chooses the address in PayPal when they pay.') }}</p>
                @endif

                @if ($order->ship_to_phone)
                    <p class="mute">{{ $order->ship_to_phone }}</p>
                @endif

                @if ($order->shipping_service)
                    <p class="mute">
                        {{ __('Chosen postage: :service', ['service' => $order->shipping_service]) }}
                    </p>
                @endif
            </x-verum.account.panel>
        </div>
    </div>
</x-layouts::seller>
