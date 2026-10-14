@php
    $rules = [
        [
            'title' => __('Disclosed origin'),
            'body' => __('Mine region and treatment history, stated plainly. Never assumed, never hidden.'),
            'examples' => [__('Mogok, Myanmar'), __('No indication of heat')],
        ],
        [
            'title' => __('Independent grading'),
            'body' => __("Reports from labs we don't own, so the grading answers to the buyer, not to us."),
            'examples' => ['GIA', 'AGL', __('Report on every listing')],
        ],
        [
            'title' => __('Vetted sellers only'),
            'body' => __('Every tenant is reviewed by hand before their first listing goes live.'),
            'examples' => [__('Trade license'), __('Identity'), __('Sample listing')],
        ],
    ];
@endphp

<section class="sec std" aria-labelledby="stdTitle">
    <div class="wrap">
        <x-verum.section-head
            :kicker="__('Our standard')"
            :title="__('Three rules we')"
            :emphasis="__('don’t bend')"
            title-id="stdTitle"
        />

        <div class="std__grid">
            @foreach ($rules as $index => $rule)
                <article class="std__card" data-reveal style="--d:{{ $index * 110 }}">
                    <span class="std__n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <h3>{{ $rule['title'] }}</h3>
                    <p>{{ $rule['body'] }}</p>
                    <ul class="std__eg">
                        @foreach ($rule['examples'] as $example)
                            <li>{{ $example }}</li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </div>
</section>
