@php
    use App\Models\Order;

    $tabs = [
        'to_ship' => __('To post'),
        Order::STATUS_AWAITING_PAYMENT => __('Awaiting payment'),
        'all' => __('All sales'),
    ];
@endphp

<x-layouts::seller
    :account="$account"
    :title="__('Sales')"
    :heading="__('Sales')"
    :lede="__('Confirm payment, then post the stone.')"
>
    <nav class="adminfilters" aria-label="{{ __('Filter sales') }}">
        @foreach ($tabs as $value => $label)
            <a
                href="{{ route('seller.orders', ['status' => $value]) }}"
                @class(['chip', 'is-on' => $filter === $value])
                @if ($filter === $value) aria-current="page" @endif
                wire:navigate
            >{{ $label }}@if (($counts[$value] ?? 0) > 0) <b>{{ $counts[$value] }}</b>@endif</a>
        @endforeach
    </nav>

    @if ($orders->isEmpty())
        <x-verum.account.panel :title="__('Sales')" icon="box">
            <x-verum.account.empty
                icon="box"
                :title="__('Nothing here')"
                :body="__('Orders appear the moment a buyer places one.')"
            />
        </x-verum.account.panel>
    @else
        <div class="olist">
            @foreach ($orders as $order)
                <article class="ocard">
                    <header class="ocard__head">
                        <a class="ocard__ref" href="{{ route('seller.orders.show', $order) }}" wire:navigate>
                            {{ $order->reference }}
                        </a>
                        <x-verum.order.status-pill :$order />
                        <time datetime="{{ $order->created_at?->toIso8601String() }}">
                            {{ $order->created_at?->format('j M Y') }}
                        </time>
                    </header>

                    <p class="ocard__who">{{ $order->buyer?->name }} · {{ \App\Support\Countries::name($order->ship_to_country) }}</p>

                    <x-verum.order.lines :$order />

                    <footer class="ocard__foot">
                        <b>{{ $order->total()->format() }}</b>
                        <a class="btn btn--ghost btn--sm" href="{{ route('seller.orders.show', $order) }}" wire:navigate>
                            {{ __('Open') }}
                        </a>
                    </footer>
                </article>
            @endforeach
        </div>

        <div class="pager">{{ $orders->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::seller>
