{{-- Global site chrome: announcement ticker, brand, main nav, tools. --}}
<header class="hdr" id="hdr">
    <div class="ticker">
        <p class="ticker__live"><i class="dot-live"></i>{{ __('Live') }}</p>
        <div class="ticker__m marquee" data-marquee data-speed=".35">
            <div class="marquee__track">
                @foreach ($ticker as $item)
                    <span class="tk">{!! $item !!}</span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="hdr__bar wrap">
        <x-verum.brand />

        <nav class="nav" aria-label="{{ __('Main') }}">
            @foreach ($navigation as $item)
                <a
                    href="{{ $item['url'] }}"
                    data-nav="{{ $item['key'] }}"
                    @class(['is-on' => $item['active']])
                    wire:navigate
                >
                    {{ $item['label'] }}
                    @if ($item['key'] === 'auctions')
                        <span class="nav__live" aria-label="{{ __('live') }}"></span>
                    @endif
                </a>
            @endforeach
            <span class="nav__ink" aria-hidden="true"></span>
        </nav>

        <div class="tools">
            <button class="tools__search" type="button" data-open-search aria-label="{{ __('Search stones') }}">
                <x-verum.icon name="search" />
                <span>{{ __('Search stones') }}</span>
                <kbd data-kbd>Ctrl K</kbd>
            </button>

            <a class="ibtn ibtn--hide-sm" href="{{ route('verify') }}" aria-label="{{ __('Verify a report') }}" data-tip="{{ __('Verify a report') }}" wire:navigate>
                <x-verum.icon name="shield" />
            </a>

            <x-verum.currency-picker />

            <button class="ibtn" type="button" data-open-wish aria-label="{{ __('Saved stones') }}" data-tip="{{ __('Saved stones') }}">
                <x-verum.icon name="heart" />
                <span class="badge" data-wish-count hidden>0</span>
            </button>

            <x-verum.account-menu />

            <a class="ibtn ibtn--cart" href="{{ route('cart') }}" aria-label="{{ __('Cart') }}" data-tip="{{ __('Cart') }}" id="cartIcon" wire:navigate>
                <x-verum.icon name="bag" />
                <span class="badge" data-cart-count hidden>0</span>
            </a>

            <button class="burger" type="button" id="burger" aria-label="{{ __('Open menu') }}" aria-expanded="false" aria-controls="mnav">
                <span></span><span></span>
            </button>
        </div>
    </div>
</header>

<x-verum.mobile-nav :$navigation />
