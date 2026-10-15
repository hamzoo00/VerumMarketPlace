@props(['heroStones'])

<div class="hero" id="hero" data-spy="{{ __('Intro') }}">
    <div class="hero__bg" aria-hidden="true"></div>
    <div class="hero__cols" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>

    <div class="wrap hero__grid">
        <div class="hero__copy">
            <p class="kicker" data-reveal>{{ __('Est. 1992 · Saddar Gem Market, Peshawar') }}</p>
            <h1 class="hero__title" data-split>
                {{ __('Natural') }} <span class="amp">&amp;</span><br>
                <em class="red">{{ __('Certified') }}</em><br>
                {{ __('Gemstones') }}
            </h1>
            <p class="hero__ship" data-reveal style="--d:250">
                <span>{{ __('Worldwide shipping') }}</span><i></i><span>{{ __('Insured, door to door') }}</span>
            </p>
            <p class="hero__lede" data-reveal style="--d:350">
                {{ __("Every stone graded by an independent lab, its origin disclosed, its seller vetted by hand. Exactly as stated.") }}
            </p>
            <div class="hero__ctas" data-reveal style="--d:450">
                <a class="btn btn--ruby" href="{{ route('shop') }}" data-magnetic wire:navigate>
                    {{ __('Shop gemstones') }}
                    <span class="arr" aria-hidden="true">
                        <svg><use href="#i-arrow" /></svg><svg><use href="#i-arrow" /></svg>
                    </span>
                </a>
                <a class="btn btn--ghost" href="{{ route('contact') }}" wire:navigate>
                    <x-verum.icon name="mail" />{{ __('Contact us') }}
                </a>
            </div>
        </div>

        <div class="hero__stagewrap" data-reveal="scale" style="--d:150">
            <div class="hero__stage" id="heroStage">
                <svg class="hero__ring" viewBox="0 0 500 500" aria-hidden="true">
                    <defs>
                        <path id="ringPath" d="M250,250 m-214,0 a214,214 0 1,1 428,0 a214,214 0 1,1 -428,0" />
                    </defs>
                    <circle cx="250" cy="250" r="246" class="hero__ring-ticks" />
                    <circle cx="250" cy="250" r="194" class="hero__ring-hair" />
                    <text class="hero__ring-text">
                        <textPath href="#ringPath" textLength="1335" lengthAdjust="spacing">
                            {{ __('NATURAL · INDEPENDENTLY GRADED · ORIGIN DISCLOSED · VETTED SELLERS · EST. 1992 ·') }}
                        </textPath>
                    </text>
                </svg>
                <div class="hero__disc" aria-hidden="true"></div>
                <div class="hero__sparks" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>

                @foreach ($heroStones as $index => $stone)
                    <a
                        @class(['hero__stone', 'is-active' => $index === 0])
                        data-hero="{{ $stone->gemType->slug }}"
                        href="{{ route('stones.show', $stone) }}"
                        aria-label="{{ $stone->name }}, {{ $stone->caratLabel() }}"
                        @if ($index !== 0) tabindex="-1" @endif
                        wire:navigate
                    >
                        <span class="hero__float">
                            <img
                                src="{{ asset("images/gems/hero-{$stone->gemType->slug}.webp") }}"
                                alt=""
                                width="800"
                                height="800"
                                decoding="async"
                                @if ($index === 0) fetchpriority="high" @endif
                            >
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="wrap hero__foot">
        <div class="hero__picker" role="tablist" aria-label="{{ __('Stone on the stage') }}">
            @foreach ($heroStones as $index => $stone)
                <button
                    @class(['hp', 'is-active' => $index === 0])
                    type="button"
                    role="tab"
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                    data-pick="{{ $stone->gemType->slug }}"
                >
                    <span class="hp__n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="hp__t">{{ $stone->gemType->label }}</span>
                    <span class="hp__bar"><i></i></span>
                </button>
            @endforeach
        </div>
        <button class="hero__scroll" type="button" data-scrollto="#trust">
            <span>{{ __('Scroll') }}</span><i></i>
        </button>
    </div>
</div>
