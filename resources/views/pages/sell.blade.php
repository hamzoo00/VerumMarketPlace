@php
    $requirements = [
        ['icon' => 'file', 'title' => __('A trade license'), 'body' => __('Issued in the country you ship from, in your own name or your company name.')],
        ['icon' => 'user', 'title' => __('Identity'), 'body' => __('Government photo ID for the person who will manage the account.')],
        ['icon' => 'gem', 'title' => __('A sample listing'), 'body' => __('One stone, photographed in daylight, with its lab report.')],
        ['icon' => 'box', 'title' => __('A shipping account'), 'body' => __('Any insured courier that can collect from your address.')],
    ];

    $steps = [
        ['title' => __('Apply'), 'body' => __('Send the four items above. We review by hand, usually within three days.')],
        ['title' => __('Verification call'), 'body' => __('A short call to confirm who you are and what you trade in.')],
        ['title' => __('List your first stone'), 'body' => __('We grade the photography and wording with you, once.')],
        ['title' => __('Get paid'), 'body' => __('Escrow releases to you after the buyer confirms the stone.')],
    ];
@endphp

<x-layouts::public
    :title="__('Sell on :app', ['app' => config('app.name')])"
    :description="__('Apply to sell certified gemstones on :app. Vetted sellers only: trade license, identity, and one sample listing.', ['app' => config('app.name')])"
>
    <header class="sellh wrap">
        <nav class="crumbs" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ __('Sell') }}</span>
        </nav>

        <p class="kicker" data-reveal>{{ __('For cutters, miners and dealers') }}</p>
        <h1 class="phead__title" data-split>{{ __('Sell to buyers who') }} <em>{{ __('read the report.') }}</em></h1>
        <p class="lede" data-reveal>
            {{ __(':app is a closed marketplace. Sellers are vetted by hand, and every listing carries an independent report. Buyers pay into escrow; you are paid once the stone is confirmed.', ['app' => config('app.name')]) }}
        </p>
        <div class="abt__ctas" data-reveal style="--d:150">
            <a class="btn btn--ruby" href="{{ route('seller.register') }}" wire:navigate>
                <x-verum.icon name="gem" />{{ __('Apply to sell') }}
            </a>
            <a class="btn btn--ghost" href="{{ route('sellers.index') }}" wire:navigate>{{ __('See current sellers') }}</a>
        </div>
    </header>

    <section class="sec" aria-labelledby="sellReqTitle">
        <div class="wrap">
            <x-verum.section-head
                :kicker="__('What we need')"
                :title="__('Four things.')"
                :emphasis="__('Nothing more.')"
                title-id="sellReqTitle"
            />
            <ul class="trust__grid">
                @foreach ($requirements as $index => $requirement)
                    <li class="trust__item" data-reveal style="--d:{{ $index * 90 }}">
                        <span class="trust__n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="trust__ic"><x-verum.icon :name="$requirement['icon']" /></span>
                        <h3>{{ $requirement['title'] }}</h3>
                        <p>{{ $requirement['body'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="sec aucsteps" aria-labelledby="sellStepsTitle">
        <div class="wrap">
            <x-verum.section-head
                :kicker="__('How it works')"
                :title="__('From application')"
                :emphasis="__('to payout.')"
                title-id="sellStepsTitle"
            />
            <ol class="steps">
                @foreach ($steps as $index => $step)
                    <li class="step" data-reveal style="--d:{{ $index * 80 }}">
                        <span class="step__n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
</x-layouts::public>
