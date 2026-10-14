@props([
    'total',
    'activeSort' => 'newest',
    'activeType' => null,
    'untreatedOnly' => false,
])

@php
    $sorts = [
        'newest' => __('Newest first'),
        'price-asc' => __('Price: low to high'),
        'price-desc' => __('Price: high to low'),
        'carat-desc' => __('Carat: largest first'),
    ];
    $activeCount = (int) ($activeType !== null) + (int) $untreatedOnly;
@endphp

<div class="toolbar">
    <button class="btn btn--ghost btn--sm toolbar__filters" type="button" data-open-filters>
        <x-verum.icon name="sliders" />{{ __('Filters') }}
        @if ($activeCount > 0)
            <span class="toolbar__fc">{{ $activeCount }}</span>
        @endif
    </button>

    <p class="toolbar__count" aria-live="polite">
        <strong>{{ $total }}</strong> <span>{{ trans_choice('gemstone|gemstones', $total) }}</span>
    </p>

    <div class="toolbar__right">
        {{-- Native select keeps sorting functional without JavaScript. --}}
        <form method="GET" action="{{ route('shop') }}" class="sortform">
            @if ($activeType)
                <input type="hidden" name="type" value="{{ $activeType }}">
            @endif
            @if ($untreatedOnly)
                <input type="hidden" name="untreated" value="1">
            @endif
            <label class="sr-only" for="sortBy">{{ __('Sort by') }}</label>
            <select id="sortBy" name="sort" onchange="this.form.submit()">
                @foreach ($sorts as $value => $label)
                    <option value="{{ $value }}" @selected($activeSort === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <noscript><button class="btn btn--sm" type="submit">{{ __('Sort') }}</button></noscript>
        </form>

        <div class="seg" role="group" aria-label="{{ __('Layout') }}">
            <button type="button" data-view="grid" aria-pressed="true" aria-label="{{ __('Grid view') }}">
                <x-verum.icon name="grid" />
            </button>
            <button type="button" data-view="list" aria-pressed="false" aria-label="{{ __('List view') }}">
                <x-verum.icon name="list" />
            </button>
        </div>
    </div>
</div>
