<x-layouts::public
    :title="__('Checkout')"
    :description="__('Complete your :app order.', ['app' => config('app.name')])"
    robots="noindex, nofollow"
>
    <x-verum.page-head
        :title="__('Check')"
        :emphasis="__('out')"
        :crumbs="[__('Cart') => route('cart'), __('Checkout') => null]"
    />

    <div class="wrap checkout">
        @if (config('verum.demo_mode'))
            <div class="notice" role="status">
                <x-verum.icon name="info" />
                <div>
                    <b>{{ __('Checkout is not live yet.') }}</b>
                    <p>{{ __('Payments, escrow and order creation arrive with the backend. Nothing here charges a card.') }}</p>
                </div>
            </div>
        @endif

        {{-- Steps are rendered client-side from the local cart; see checkout.js. --}}
        <div class="costeps" id="checkoutBody" data-checkout-root></div>
    </div>
</x-layouts::public>
