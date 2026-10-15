@props(['gemTypes'])

<section class="sec sec--tight-top cats-sec" data-spy="{{ __('Stones') }}" aria-labelledby="catsTitle">
    <div class="wrap">
        <x-verum.section-head
            :kicker="__('Shop by stone')"
            :title="__('Five stones.')"
            :emphasis="__('One standard.')"
            title-id="catsTitle"
            :aside="__('Open a stone to see where it comes from. Every listing names its mine region and its treatment.')"
        />

        <div class="cats" id="cats">
            @foreach ($gemTypes as $index => $type)
                <a
                    @class(['cat', 'is-open' => $loop->first])
                    href="{{ route('shop', ['type' => $type->slug]) }}"
                    style="--gem:{{ $type->accent_color }}; --d:{{ $index * 80 }}"
                    data-cat
                    data-reveal
                    wire:navigate
                >
                    <span class="cat__glow" aria-hidden="true"></span>
                    <span class="cat__idx">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="cat__v" aria-hidden="true">{{ $type->label }}</span>
                    <span class="cat__body">
                        <span class="cat__plate plate">
                            <img
                                src="{{ asset("images/gems/{$type->slug}-card.webp") }}"
                                alt=""
                                width="640"
                                height="640"
                                loading="lazy"
                                decoding="async"
                                draggable="false"
                            >
                        </span>
                        <span class="cat__text">
                            <span class="cat__name">{{ $type->label }}</span>
                            <span class="cat__facts">
                                <span>{{ $type->mineral }}</span>
                                <span>{{ __('Mohs :rating', ['rating' => $type->mohs]) }}</span>
                                <span>{{ trans_choice(':count stone|:count stones', $type->stones_count) }}</span>
                            </span>
                            <span class="cat__note">{{ $type->note }}</span>
                            <span class="cat__go">
                                {{ __('Shop :type', ['type' => mb_strtolower($type->plural)]) }}<x-verum.icon name="arrow" />
                            </span>
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
