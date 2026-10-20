@props([
    'families' => [],
    'activeCategory' => null,
    'activeKind' => null,
    'untreatedOnly' => false,
    'activeSort' => 'newest',
    'activeType' => null,
    'live' => false,
])

@php
    $kinds = [
        '' => __('Everything'),
        \App\Models\Category::KIND_STONE => __('Loose stones'),
        \App\Models\Category::KIND_JEWELLERY => __('Jewellery'),
    ];

    $types = [
        '' => __('Any'),
        'fixed' => __('Buy now'),
        'auction' => __('Auction'),
    ];

    $familyOptions = ['' => __('All families')]
        + $families->pluck('name', 'slug')->all();
@endphp

{{--
    Inside the Livewire browser each control binds straight to the component,
    so a choice applies without a reload. Standalone it stays a GET form, so
    a filtered view keeps a shareable, crawlable URL and works without JS.
--}}
<aside class="filters" id="filters" aria-label="{{ __('Filters') }}">
    <form method="GET" action="{{ route('shop') }}" @unless ($live) data-filter-form @endunless>
        <div class="filters__head">
            <h2>{{ __('Filters') }}</h2>

            @if ($live)
                <button class="link" type="button" wire:click="clearAll">{{ __('Clear all') }}</button>
            @else
                <a class="link" href="{{ route('shop') }}" wire:navigate>{{ __('Clear all') }}</a>
            @endif

            <button class="ibtn filters__x" type="button" data-close-filters aria-label="{{ __('Close filters') }}">
                <x-verum.icon name="close" />
            </button>
        </div>

        <div class="filters__body">
            @unless ($live)
                <input type="hidden" name="sort" value="{{ $activeSort }}">
            @endunless

            <x-verum.form.radio-group
                name="kind"
                :legend="__('Listing')"
                :options="$kinds"
                :selected="$activeKind ?? ''"
                :model="$live ? 'kind' : null"
            />

            @if (count($familyOptions) > 1)
                <x-verum.form.radio-group
                    name="category"
                    :legend="__('Family')"
                    :options="$familyOptions"
                    :selected="$activeCategory?->slug ?? ''"
                    :model="$live ? 'category' : null"
                />
            @endif

            <x-verum.form.radio-group
                name="type"
                :legend="__('Buying')"
                :options="$types"
                :selected="$activeType ?? ''"
                :model="$live ? 'type' : null"
            />

            <fieldset class="fgrp">
                <legend class="lbl">{{ __('Treatment') }}</legend>
                <div class="chklist">
                    <label class="chk">
                        <input
                            type="checkbox"
                            @if ($live) wire:model.live="untreated" @else name="untreated" value="1" @endif
                            @checked($untreatedOnly)
                        >
                        <span>{{ __('Untreated only') }}</span>
                    </label>
                </div>
            </fieldset>
        </div>

        @unless ($live)
            {{-- Visible only in the mobile sheet; on desktop a choice applies itself. --}}
            <div class="filters__foot">
                <button class="btn btn--ruby btn--block" type="submit">{{ __('Apply filters') }}</button>
            </div>
        @endunless
    </form>
</aside>
