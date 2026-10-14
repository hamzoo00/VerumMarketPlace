@php
    $steps = [
        ['icon' => 'user', 'title' => __('Register to bid'), 'body' => __('One account, verified once. No deposit for lots under $5,000.')],
        ['icon' => 'gavel', 'title' => __('Place your maximum'), 'body' => __('We bid for you in increments, never above your limit.')],
        ['icon' => 'clock', 'title' => __('Anti-sniping clock'), 'body' => __('A late bid extends the lot by two minutes, every time.')],
        ['icon' => 'box', 'title' => __('Pay and receive'), 'body' => __('Escrow releases to the seller once you confirm the stone.')],
    ];
@endphp

<section class="sec aucsteps" aria-labelledby="aucStepsTitle">
    <div class="wrap">
        <x-verum.section-head
            :kicker="__('How bidding works')"
            :title="__('Four steps.')"
            :emphasis="__('No surprises.')"
            title-id="aucStepsTitle"
        />

        <ol class="steps">
            @foreach ($steps as $index => $step)
                <li class="step" data-reveal style="--d:{{ $index * 80 }}">
                    <span class="step__n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="step__ic"><x-verum.icon :name="$step['icon']" /></span>
                    <h3>{{ $step['title'] }}</h3>
                    <p>{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
