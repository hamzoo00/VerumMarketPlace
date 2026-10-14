@props(['single' => false])

<section @class(['splitcta', 'splitcta--single' => $single]) aria-label="{{ __('Buy or sell') }}">
    <a class="splitcta__half" href="{{ route('shop') }}" wire:navigate>
        <span class="splitcta__k">{{ __('For buyers') }}</span>
        <strong>{{ __('Find your stone') }}</strong>
        <span class="splitcta__go">{{ __('Shop gemstones') }}<x-verum.icon name="arrow" /></span>
    </a>

    @unless ($single)
        <a class="splitcta__half splitcta__half--b" href="{{ route('sell') }}" wire:navigate>
            <span class="splitcta__k">{{ __('For sellers') }}</span>
            <strong>{{ __('Reach serious buyers') }}</strong>
            <span class="splitcta__go">{{ __('Sell on Verum') }}<x-verum.icon name="arrow" /></span>
        </a>
    @endunless
</section>
