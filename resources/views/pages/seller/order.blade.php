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

    <div class="ogrid">
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
                    <p class="mute">{{ __('Confirm once the buyer has paid you.') }}</p>

                    <form method="POST" action="{{ route('seller.orders.update', $order) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="paid">
                        <button class="btn btn--ruby btn--block" type="submit">{{ __('Mark as paid') }}</button>
                    </form>
                @elseif ($order->status === Order::STATUS_PAID)
                    <p class="mute">{{ __('Post the stone, then add the tracking details.') }}</p>

                    <form method="POST" action="{{ route('seller.orders.update', $order) }}" class="cform">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="dispatched">

                        <div class="fgrid">
                            <x-verum.form.field name="tracking_carrier" :label="__('Carrier')" />
                            <x-verum.form.field name="tracking_number" :label="__('Tracking number')" />
                        </div>

                        <button class="btn btn--ruby btn--block" type="submit">{{ __('Mark as dispatched') }}</button>
                    </form>
                @elseif ($order->status === Order::STATUS_DISPATCHED)
                    <form method="POST" action="{{ route('seller.orders.update', $order) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="delivered">
                        <button class="btn btn--ghost btn--block" type="submit">{{ __('Mark as delivered') }}</button>
                    </form>
                @else
                    <p class="mute">{{ __('Nothing left to do on this one.') }}</p>
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
                        <small class="mute">{{ __('The listing goes back on sale.') }}</small>
                    </form>
                @endif
            </x-verum.account.panel>

            <x-verum.account.panel :title="__('Post to')" icon="pin">
                <address class="oaddr">{{ $order->addressLines() }}</address>

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
