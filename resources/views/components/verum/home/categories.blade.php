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
                    class="cat"
                    href="{{ route('shop', ['type' => $type->slug]) }}"
                    style="--gem:{{ $type->accent_color }}; --d:{{ $index * 70 }}"
                    data-reveal
                    wire:navigate
                >
                    <span class="cat__plate plate">
                        <img
                            src="{{ asset("images/gems/{$type->slug}-160.webp") }}"
                            alt=""
                            width="160"
                            height="160"
                            loading="lazy"
                            decoding="async"
                        >
                    </span>
                    <span class="cat__body">
                        <strong class="cat__name">{{ $type->plural }}</strong>
                        <span class="cat__meta">{{ $type->mineral }} · {{ __('Mohs') }} {{ $type->mohs }}</span>
                        <span class="cat__note">{{ $type->note }}</span>
                        <span class="cat__n">{{ trans_choice(':count stone|:count stones', $type->stones_count) }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
