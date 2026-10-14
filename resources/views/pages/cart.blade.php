<x-layouts::public
    :title="__('Your cart')"
    :description="__('Review the stones in your cart before checkout.')"
    robots="noindex, nofollow"
>
    <x-verum.page-head
        :title="__('Your')"
        :emphasis="__('cart')"
        :crumbs="[__('Cart') => null]"
    />

    <div class="wrap cart">
        {{--
            Cart contents live client-side until buyer accounts and server-side
            carts land; #cartBody is populated by resources/js/verum/cart.js.
        --}}
        <div class="cart__grid" id="cartBody" data-cart-root>
            <div class="empty" data-cart-empty>
                <x-verum.icon name="bag" class="empty__ic" />
                <h3>{{ __('Your cart is empty.') }}</h3>
                <p>{{ __('Stones you add will appear here.') }}</p>
                <a class="btn btn--ruby btn--sm" href="{{ route('shop') }}" wire:navigate>{{ __('Shop gemstones') }}</a>
            </div>
        </div>
    </div>
</x-layouts::public>
