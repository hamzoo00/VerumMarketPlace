@php
    $stats = [
        ['value' => 34, 'label' => __('Years trading')],
        ['value' => 12, 'suffix' => 'k+', 'label' => __('Stones certified')],
        ['value' => 61, 'label' => __('Verified sellers')],
        ['value' => 94, 'label' => __('Countries shipped to')],
    ];
@endphp

<section class="sec sec--tight-top">
    <div class="wrap">
        <ul class="stats">
            @foreach ($stats as $index => $stat)
                <li data-reveal style="--d:{{ $index * 90 }}">
                    <strong>
                        <span data-counter="{{ $stat['value'] }}">{{ $stat['value'] }}</span>
                        @isset($stat['suffix'])<small>{{ $stat['suffix'] }}</small>@endisset
                    </strong>
                    <span>{{ $stat['label'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>
