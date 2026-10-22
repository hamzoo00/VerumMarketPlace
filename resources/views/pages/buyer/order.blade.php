<x-layouts::account-area
    :title="$order->reference"
    :heading="$order->reference"
    :lede="__('Placed :when', ['when' => $order->created_at?->format('j M Y')])"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <a class="link" href="{{ route('buyer.orders') }}" wire:navigate>{{ __('← All orders') }}</a>

    <div class="ogrid">
        <x-verum.account.panel :title="__('What you bought')" icon="gem">
            <x-verum.order.lines :$order />
            <x-verum.order.summary :$order />
        </x-verum.account.panel>

        <div class="ogrid__side">
            <x-verum.account.panel :title="__('Status')" icon="box">
                <x-verum.order.status-pill :$order />

                {{-- Each step shows when it happened, not merely that it
                     did: a dispute asks when the parcel was posted. --}}
                <ul class="otrack">
                    <li @class(['is-done' => $order->created_at !== null])>
                        {{ __('Order placed') }}
                        <time>{{ $order->created_at?->format('j M, H:i') }}</time>
                    </li>
                    <li @class(['is-done' => $order->paid_at !== null])>
                        {{ __('Payment confirmed') }}
                        <time>{{ $order->paid_at?->format('j M, H:i') }}</time>
                    </li>
                    <li @class(['is-done' => $order->dispatched_at !== null])>
                        {{ __('Dispatched') }}
                        <time>{{ $order->dispatched_at?->format('j M, H:i') }}</time>
                    </li>
                    <li @class(['is-done' => $order->delivered_at !== null])>
                        {{ __('Delivered') }}
                        <time>{{ $order->delivered_at?->format('j M, H:i') }}</time>
                    </li>
                </ul>

                @if ($order->tracking_number)
                    <p class="otrack__num">
                        <x-verum.icon name="globe" />
                        {{ $order->tracking_carrier }} · <b>{{ $order->tracking_number }}</b>
                    </p>
                @endif

                @if ($order->isCancelled() && $order->cancelled_reason)
                    <p class="mute">{{ $order->cancelled_reason }}</p>
                @endif
            </x-verum.account.panel>

            <x-verum.account.panel :title="__('Delivery address')" icon="pin">
                <address class="oaddr">{{ $order->addressLines() }}</address>

                @if ($order->ship_to_phone)
                    <p class="mute">{{ $order->ship_to_phone }}</p>
                @endif
            </x-verum.account.panel>

            <x-verum.account.panel :title="__('Seller')" icon="shield">
                <a class="link" href="{{ route('sellers.show', $order->seller) }}" wire:navigate>
                    {{ $order->seller?->name }}
                </a>

                <a class="btn btn--ghost btn--sm" href="{{ route('chat.index') }}" wire:navigate>
                    <x-verum.icon name="chat" />{{ __('Message the seller') }}
                </a>
            </x-verum.account.panel>
        </div>
    </div>
</x-layouts::account-area>
