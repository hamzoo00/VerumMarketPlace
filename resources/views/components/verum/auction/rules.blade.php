@php
    $snipe = config('verum_auctions.anti_snipe');
    $window = (int) round($snipe['window_seconds'] / 60);
    $extend = (int) round($snipe['extend_seconds'] / 60);

    $rules = [
        [
            'icon' => 'gavel',
            'title' => __('Bidding opens at the start price'),
            'body' => __('The seller sets it. The first bid must meet it; after that every bid clears the one before by the seller\'s increment.'),
        ],
        [
            'icon' => 'lock',
            'title' => __('A reserve is hidden'),
            'body' => __('Some lots carry a reserve. You are told whether it has been met, never the figure, and the lot does not sell below it.'),
        ],
        [
            'icon' => 'clock',
            'title' => __('Late bids extend the clock'),
            'body' => trans_choice(
                'A bid in the final minute adds another minute.|A bid in the final :window minutes adds :extend more.',
                $window,
                ['window' => $window, 'extend' => $extend],
            ),
        ],
        [
            'icon' => 'check',
            'title' => __('Highest bid wins'),
            'body' => __('When the clock runs out the leading bid takes the lot, provided any reserve was met. Sellers cannot bid on their own lots.'),
        ],
    ];
@endphp

<section class="sec aucsteps" aria-labelledby="aucRulesTitle">
    <div class="wrap">
        <x-verum.section-head
            :kicker="__('How bidding works')"
            :title="__('Four rules.')"
            :emphasis="__('No surprises.')"
            title-id="aucRulesTitle"
        />

        <ol class="steps">
            @foreach ($rules as $index => $rule)
                <li class="step" data-reveal style="--d:{{ $index * 80 }}">
                    <span class="step__n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="step__ic"><x-verum.icon :name="$rule['icon']" /></span>
                    <h3>{{ $rule['title'] }}</h3>
                    <p>{{ $rule['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
