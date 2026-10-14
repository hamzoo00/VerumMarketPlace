@props([
    'gemTypes',
    'activeType' => null,
    'untreatedOnly' => false,
    'activeSort' => 'newest',
])

{{--
    Filters submit as a GET form so every filtered view has a shareable,
    crawlable URL instead of client-only state.
--}}
<aside class="filters" id="filters" aria-label="{{ __('Filters') }}">
    <form method="GET" action="{{ route('shop') }}" id="filterForm">
        <div class="filters__head">
            <h2>{{ __('Filters') }}</h2>
            <a class="link" href="{{ route('shop') }}" wire:navigate>{{ __('Clear all') }}</a>
            <button class="ibtn filters__x" type="button" data-close-filters aria-label="{{ __('Close filters') }}">
                <x-verum.icon name="close" />
            </button>
        </div>

        <div class="filters__body" id="filterBody">
            <input type="hidden" name="sort" value="{{ $activeSort }}">

            <fieldset class="fgrp">
                <legend class="lbl">{{ __('Stone') }}</legend>
                <label class="chk">
                    <input type="radio" name="type" value="" @checked($activeType === null)>
                    <span>{{ __('All stones') }}</span>
                </label>
                @foreach ($gemTypes as $type)
                    <label class="chk">
                        <input type="radio" name="type" value="{{ $type->slug }}" @checked($activeType === $type->slug)>
                        <span>{{ $type->plural }}</span>
                        <small>{{ $type->stones_count }}</small>
                    </label>
                @endforeach
            </fieldset>

            <fieldset class="fgrp">
                <legend class="lbl">{{ __('Treatment') }}</legend>
                <label class="chk">
                    <input type="checkbox" name="untreated" value="1" @checked($untreatedOnly)>
                    <span>{{ __('Untreated only') }}</span>
                </label>
            </fieldset>
        </div>

        <div class="filters__foot">
            <button class="btn btn--ruby btn--block" type="submit">{{ __('Apply filters') }}</button>
        </div>
    </form>
</aside>
