@php
    $topics = [
        'shipping' => [
            'title' => __('Shipping policy'),
            'items' => [
                [__('Where do you ship?'), __('To 94 countries, door to door, with an insured and tracked courier. Some countries require an import permit for gemstones; we tell you before you pay.')],
                [__('What does shipping cost?'), __('Quoted at checkout from your address and the declared value. Insurance is included, never optional.')],
                [__('How long does it take?'), __('Most sellers dispatch within 2–4 business days. Transit is typically 3–7 business days.')],
            ],
        ],
        'returns' => [
            'title' => __('Returns'),
            'items' => [
                [__('Can I return a stone?'), __('Yes. Fourteen days from delivery, for any reason, provided the stone and its report come back together and unaltered.')],
                [__("What if it doesn't match the report?"), __('Full refund including both shipping legs. Send us photographs and we handle the rest with the seller.')],
                [__('Are auction lots returnable?'), __('Yes, on the same 14-day terms. A winning bid is a purchase, not a deposit.')],
            ],
        ],
        'certification' => [
            'title' => __('Certification guide'),
            'items' => [
                [__('Which labs do you use?'), __("GIA and AGL for most stones. We never grade our own listings, and we don't own a lab.")],
                [__('What does the report cover?'), __('Species and variety, weight, measurements, cut, colour, transparency, treatment, and an origin opinion where the lab offers one.')],
                [__('What does "no indication of heat" mean?'), __('The lab found no evidence of heat treatment. For ruby and sapphire this materially affects value, which is why we print it on the listing.')],
            ],
        ],
        'care' => [
            'title' => __('Gem care guide'),
            'items' => [
                [__('How do I clean a gemstone?'), __('Warm water, a drop of plain soap, a soft brush. Avoid ultrasonic cleaners for emerald, opal and any oiled or fracture-filled stone.')],
                [__('Can I wear it every day?'), __('Corundum (ruby, sapphire) at Mohs 9 takes daily wear well. Emerald and amethyst prefer occasional wear and a protective setting.')],
                [__('How should I store it?'), __('Separately, in a soft pouch. Harder stones scratch softer ones in the same box.')],
            ],
        ],
    ];
@endphp

<x-layouts::public
    :title="__('Help & policies')"
    :description="__('Shipping, returns, certification and gem care. Plain answers to the questions buyers actually ask.')"
>
    <x-verum.page-head
        :title="__('Help &')"
        :emphasis="__('policies')"
        :lede="__('Shipping, returns, certification and care. If something is still unclear, ask us.')"
        :crumbs="[__('Help') => null]"
    />

    <div class="wrap help">
        <nav class="help__toc" aria-label="{{ __('Topics') }}">
            @foreach ($topics as $anchor => $topic)
                <a href="#{{ $anchor }}">{{ $topic['title'] }}</a>
            @endforeach
        </nav>

        <div class="help__body">
            @foreach ($topics as $anchor => $topic)
                <section class="help__sec" id="{{ $anchor }}" aria-labelledby="{{ $anchor }}Title">
                    <h2 class="h3" id="{{ $anchor }}Title">{{ $topic['title'] }}</h2>
                    <div class="acc">
                        @foreach ($topic['items'] as [$question, $answer])
                            <details class="acc__item">
                                <summary>{{ $question }}<x-verum.icon name="chev" /></summary>
                                <div class="acc__body"><p>{{ $answer }}</p></div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <x-verum.split-cta single />
</x-layouts::public>
