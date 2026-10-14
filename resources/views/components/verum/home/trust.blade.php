@php
    $promises = [
        ['icon' => 'leaf', 'title' => __('100% natural stones'), 'body' => __('No synthetics, no glass, no composites. Any treatment is named on the listing.')],
        ['icon' => 'badge', 'title' => __('GIA / AGL certified'), 'body' => __("Graded by labs we don't own. The report number sits on every listing.")],
        ['icon' => 'scale', 'title' => __('Secure payments'), 'body' => __('Paid into escrow. The seller is paid only after you confirm the stone.')],
        ['icon' => 'globe', 'title' => __('Worldwide shipping'), 'body' => __('Insured and tracked, door to door, to 94 countries.')],
    ];
@endphp

<section class="sec trust" id="trust" aria-labelledby="trustTitle">
    <div class="wrap">
        <h2 class="sr-only" id="trustTitle">{{ __('What every stone comes with') }}</h2>
        <ul class="trust__grid">
            @foreach ($promises as $index => $promise)
                <li class="trust__item" data-reveal style="--d:{{ $index * 90 }}">
                    <span class="trust__n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="trust__ic"><x-verum.icon :name="$promise['icon']" /></span>
                    <h3>{{ $promise['title'] }}</h3>
                    <p>{{ $promise['body'] }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>
