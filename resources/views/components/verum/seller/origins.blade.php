@php
    // Mine regions the marketplace buys from, shown as a reference table.
    $origins = [
        ['region' => __('Mogok, Myanmar'), 'stones' => __('Ruby, star sapphire, spinel'), 'note' => __('The benchmark for red. Marble-hosted, strongly fluorescent.')],
        ['region' => __('Ratnapura, Sri Lanka'), 'stones' => __('Sapphire, padparadscha'), 'note' => __('Alluvial gravels. Bright blues that hold colour in shade.')],
        ['region' => __('Muzo, Colombia'), 'stones' => __('Emerald, trapiche'), 'note' => __('Warm, saturated green. Minor oil is normal and disclosed.')],
        ['region' => __('Kagem & Kariba, Zambia'), 'stones' => __('Emerald, amethyst'), 'note' => __('Cooler bluish green; deep purple amethyst.')],
        ['region' => __('Swat & Hunza, Pakistan'), 'stones' => __('Emerald, ruby, topaz'), 'note' => __('Small, intense emerald, often with no oil at all.')],
        ['region' => __('Katlang, Pakistan'), 'stones' => __('Imperial topaz'), 'note' => __('One of very few natural pink topaz sources worldwide.')],
    ];
@endphp

<section class="sec origins" aria-labelledby="originsTitle">
    <div class="wrap">
        <x-verum.section-head
            :kicker="__('Where stones come from')"
            :title="__('Origin, named')"
            :emphasis="__('on the listing.')"
            title-id="originsTitle"
        />

        <div class="tablewrap" data-reveal>
            <table class="otable">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Region') }}</th>
                        <th scope="col">{{ __('Stones') }}</th>
                        <th scope="col">{{ __('What to expect') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($origins as $origin)
                        <tr>
                            <th scope="row">{{ $origin['region'] }}</th>
                            <td>{{ $origin['stones'] }}</td>
                            <td>{{ $origin['note'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
