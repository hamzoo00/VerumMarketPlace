<x-layouts::account-area
    :title="__('Orders')"
    :heading="__('Orders')"
    :lede="__('Purchases, payments and shipping.')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    @if ($orders->isEmpty())
        <x-verum.account.panel :title="__('Orders')" icon="box">
            <x-verum.account.empty
                icon="box"
                :title="__('No orders yet')"
                :body="__('Your purchases and tracking will show up here.')"
            />
            <a class="btn btn--ghost btn--sm" href="{{ route('shop') }}" wire:navigate>
                {{ __('Shop gemstones') }}
            </a>
        </x-verum.account.panel>
    @else
        <div class="olist">
            @foreach ($orders as $order)
                <article class="ocard">
                    <header class="ocard__head">
                        <a class="ocard__ref" href="{{ route('buyer.orders.show', $order) }}" wire:navigate>
                            {{ $order->reference }}
                        </a>
                        <x-verum.order.status-pill :$order />
                        <time datetime="{{ $order->created_at?->toIso8601String() }}">
                            {{ $order->created_at?->format('j M Y') }}
                        </time>
                    </header>

                    <p class="ocard__who">{{ $order->seller?->name }}</p>

                    <x-verum.order.lines :$order />

                    <footer class="ocard__foot">
                        <b>{{ $order->total()->withLocal() }}</b>
                        <a class="btn btn--ghost btn--sm" href="{{ route('buyer.orders.show', $order) }}" wire:navigate>
                            {{ __('View order') }}
                        </a>
                    </footer>
                </article>
            @endforeach
        </div>

        <div class="pager">{{ $orders->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::account-area>
