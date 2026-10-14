{{-- Scrolling marquee of the four headline promises. --}}
@php
    $items = [
        ['text' => __('100% natural stones')],
        ['em' => __('GIA / AGL'), 'text' => __('certified')],
        ['text' => __('Secure payments')],
        ['em' => __('Worldwide'), 'text' => __('shipping')],
    ];
@endphp

<div class="band" aria-label="{{ __('Our promises') }}">
    <div class="marquee" data-marquee data-speed=".9">
        <div class="marquee__track">
            @foreach ($items as $item)
                <span class="band__item">
                    @isset($item['em'])<em>{{ $item['em'] }}</em> @endisset{{ $item['text'] }}
                </span>
                <svg class="band__star" aria-hidden="true"><use href="#i-spark" /></svg>
            @endforeach
        </div>
    </div>
</div>
