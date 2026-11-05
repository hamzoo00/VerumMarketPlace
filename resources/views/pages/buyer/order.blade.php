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

    {{-- Re-read when the seller or an admin changes the order. --}}
    <div class="ogrid" data-live-region="order">
        <div class="ogrid__main">
            <x-verum.account.panel :title="__('What you bought')" icon="gem">
                <x-verum.order.lines :$order />
                <x-verum.order.summary :$order />
            </x-verum.account.panel>

            @unless ($order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT || $order->isCancelled())
                {{-- The carrier's scans and the seller's updates, as they
                     arrive: a scan or an update re-reads this region. --}}
                <x-verum.account.panel :title="__('Delivery')" icon="globe">
                    <x-verum.order.tracking :$order />
                    <x-verum.order.timeline
                        :$order
                        :empty="__('Not posted yet. Updates appear here as they happen — from the seller now, and from the carrier once it is on its way.')"
                    />
                </x-verum.account.panel>
            @endunless
        </div>

        <div class="ogrid__side">
            <x-verum.account.panel :title="__('Status')" icon="box">
                <x-verum.order.status-pill :$order />

                <x-verum.order.payment :$order />

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

                <a class="btn btn--ghost btn--sm" href="{{ route('buyer.messages') }}" wire:navigate>
                    <x-verum.icon name="chat" />{{ __('Message the seller') }}
                </a>
            </x-verum.account.panel>
        </div>
    </div>
</x-layouts::account-area>
