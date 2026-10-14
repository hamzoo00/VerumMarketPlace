@php
    $milestones = [
        ['year' => '1992', 'title' => __('A single loupe'), 'body' => __('A counter in the Saddar Gem Market, Peshawar. The rule from day one: describe the stone exactly as it is.')],
        ['year' => '2004', 'title' => __('To the source'), 'body' => __('The second generation buys direct from cutters in Ratnapura and Mogok, and records origin for every stone.')],
        ['year' => '2015', 'title' => __('Independent grading'), 'body' => __("Every stone we sell now carries a report from a lab we don't own.")],
        ['year' => '2020', 'title' => __('The bazaar opens'), 'body' => __('Vetted sellers from five countries join, held to the same standard.')],
        ['year' => '2026', 'title' => __('Verum'), 'body' => __('61 verified sellers, 12k+ stones certified, shipped to 94 countries.')],
    ];
@endphp

<section class="sec tl" id="timeline" aria-labelledby="tlTitle">
    <div class="wrap">
        <x-verum.section-head
            :kicker="__('Three generations')"
            :title="__('From one loupe')"
            :emphasis="__('to sixty-one houses')"
            title-id="tlTitle"
        />

        <div class="tl__track">
            <div class="tl__line" aria-hidden="true"><i id="tlFill"></i></div>
            <ol class="tl__list">
                @foreach ($milestones as $index => $milestone)
                    <li class="tl__item" data-reveal style="--d:{{ $index * 90 }}">
                        <span class="tl__dot"></span>
                        <span class="tl__year">{{ $milestone['year'] }}</span>
                        <h3>{{ $milestone['title'] }}</h3>
                        <p>{{ $milestone['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
