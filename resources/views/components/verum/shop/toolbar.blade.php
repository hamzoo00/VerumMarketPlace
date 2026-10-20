@props([
    'total',
    'activeSort' => 'newest',
    'activeCategory' => null,
    'untreatedOnly' => false,
    'activeType' => null,
    'live' => false,
])

@php
    $sorts = [
        'newest' => __('Newest first'),
        'price-asc' => __('Price: low to high'),
        'price-desc' => __('Price: high to low'),
        'carat-desc' => __('Carat: largest first'),
    ];
    $activeCount = (int) ($activeCategory !== null) + (int) $untreatedOnly + (int) ($activeType !== null);
@endphp

<div class="toolbar">
    <button class="btn btn--ghost btn--sm toolbar__filters" type="button" data-open-filters>
        <x-verum.icon name="sliders" />{{ __('Filters') }}
        @if ($activeCount > 0)
            <span class="toolbar__fc">{{ $activeCount }}</span>
        @endif
    </button>

    <p class="toolbar__count" aria-live="polite">
        <strong data-live-count>{{ $total }}</strong> <span>{{ trans_choice('listing|listings', $total) }}</span>
    </p>

    <div class="toolbar__right">
        @if ($live)
            {{-- Bound to the component: re-sorting never reloads the page. --}}
            <div class="sortform">
                <label class="sr-only" for="sortBy">{{ __('Sort by') }}</label>
                <select id="sortBy" wire:model.live="sort">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @else
        <form method="GET" action="{{ route('shop') }}" class="sortform">
            @if ($activeCategory)
                <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
            @endif
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
        @endif
    </div>
</div>
