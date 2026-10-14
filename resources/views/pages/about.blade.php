<x-layouts::public
    :title="__('About')"
    :description="__('Three generations of gem trading, from a single counter in Peshawar to sixty-one verified sellers worldwide. Disclosed origin, independent grading.')"
>
    <header class="abt wrap">
        <nav class="crumbs" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ __('About') }}</span>
        </nav>

        <p class="kicker" data-reveal>{{ __('Three generations of gem trading') }}</p>
        <h1 class="abt__title" data-split>
            {{ __('We cut out the') }} <span class="strike"><em>{{ __('uncertainty') }}</em></span>, {{ __('not the craft.') }}
        </h1>

        <div class="abt__row">
            <p class="lede" data-reveal>
                {{ __("It began with a single loupe and a reputation for honesty in Peshawar's old gem bazaar. Today we work with cutters and mines across Sri Lanka, Myanmar, Colombia and Zambia.") }}
            </p>
            <div class="abt__side" data-reveal style="--d:150">
                <p class="abt__est">
                    <span>{{ __('Est. 1992') }}</span>
                    <span>{{ __('Saddar Gem Market, Peshawar') }}</span>
                </p>
                <div class="abt__ctas">
                    <a class="btn btn--ruby" href="{{ route('shop') }}" wire:navigate>
                        {{ __('Explore the collection') }}
                        <span class="arr" aria-hidden="true"><svg><use href="#i-arrow" /></svg><svg><use href="#i-arrow" /></svg></span>
                    </a>
                    <a class="btn btn--ghost" href="{{ route('sellers.index') }}" wire:navigate>{{ __('Meet our sellers') }}</a>
                </div>
            </div>
        </div>
    </header>

    <section class="abtphoto" aria-label="{{ __('Four stones on black velvet') }}">
        <div class="abtphoto__frame" data-reveal="clip">
            <div class="abtphoto__par" data-parallax="-0.12">
                <img
                    src="{{ asset('images/gems/about-cluster.webp') }}"
                    alt="{{ __('A ruby, a sapphire, an emerald and an amethyst on black velvet') }}"
                    loading="lazy"
                    decoding="async"
                >
            </div>
        </div>

        <x-verum.about.sample-report />
    </section>

    <x-verum.about.stats />
    <x-verum.about.timeline />
    <x-verum.about.standards />

    <x-verum.split-cta single />
</x-layouts::public>
