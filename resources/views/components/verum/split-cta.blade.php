@props(['single' => false])

<section
    {{ $attributes->class(['splitcta', 'splitcta--single' => $single]) }}
    aria-label="{{ $single ? __('Start') : __('Buy or sell') }}"
>
    <a class="splitcta__half splitcta__buy" href="{{ route('shop') }}" wire:navigate>
        @if ($single)
            <span class="kicker">{{ __("If we wouldn't buy it ourselves, we don't sell it") }}</span>
            <span class="splitcta__t">{{ __('Explore the') }} <em>{{ __('collection') }}</em></span>
        @else
            <span class="kicker">{{ __('For buyers') }}</span>
            <span class="splitcta__t">{{ __('Find your') }} <em>{{ __('stone') }}</em></span>
            <span class="splitcta__p">{{ __('Filter by origin, cut and treatment. Every listing ships with its report.') }}</span>
        @endif

        <span class="splitcta__go" aria-hidden="true"><x-verum.icon name="arrow" /></span>
    </a>

    @unless ($single)
        <a class="splitcta__half splitcta__sell" href="{{ route('sell') }}" wire:navigate>
            <span class="kicker">{{ __('For sellers') }}</span>
            <span class="splitcta__t">{{ __('Sell on') }} <em>{{ __('Verum') }}</em></span>
            <span class="splitcta__p">{{ __('Apply with your trade license. Reviewed by hand within 3 business days.') }}</span>
            <span class="splitcta__go" aria-hidden="true"><x-verum.icon name="arrow" /></span>
        </a>
    @endunless
</section>
