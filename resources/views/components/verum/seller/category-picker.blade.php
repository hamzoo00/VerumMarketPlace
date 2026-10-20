@props([
    'kinds' => [],
    'selected' => null,
])

@php
    $kindLabels = [
        \App\Models\Category::KIND_STONE => __('Loose stones'),
        \App\Models\Category::KIND_JEWELLERY => __('Jewellery'),
    ];
    $activeKind = $selected?->kind ?? $kinds[0] ?? \App\Models\Category::KIND_STONE;
@endphp

{{--
    Category browser: pick a branch, then drill down a column at a time.
    Children are fetched from seller.categories rather than shipping 287
    nodes into every page.
--}}
<div
    class="catpick"
    data-category-picker
    data-endpoint="{{ route('seller.categories', ['kind' => '__KIND__']) }}"
    data-kind="{{ $activeKind }}"
>
    <input
        type="hidden"
        name="category_id"
        value="{{ old('category_id', $selected?->id) }}"
        data-category-input
    >

    @if (count($kinds) > 1)
        <div class="catpick__kinds" role="group" aria-label="{{ __('What are you listing?') }}">
            @foreach ($kinds as $kind)
                <button
                    type="button"
                    @class(['chip', 'is-on' => $kind === $activeKind])
                    data-kind-choice="{{ $kind }}"
                >{{ $kindLabels[$kind] ?? $kind }}</button>
            @endforeach
        </div>
    @endif

    <div class="catpick__chosen" data-category-chosen>
        @if ($selected)
            <x-verum.icon name="check" />
            <span>{{ $selected->breadcrumb() }}</span>
        @else
            <span class="catpick__none">{{ __('No category chosen yet') }}</span>
        @endif
    </div>

    <button class="btn btn--ghost btn--sm" type="button" data-category-open>
        {{ $selected ? __('Change category') : __('Browse categories') }}
    </button>

    @error('category_id')
        <p class="field__err" role="alert" data-server="true">{{ $message }}</p>
    @enderror

    <div class="catmodal" data-category-modal role="dialog" aria-modal="true" aria-labelledby="catTitle" hidden>
        <div class="catmodal__scrim" data-category-close></div>

        <div class="catmodal__panel">
            <header class="catmodal__head">
                <h2 id="catTitle">{{ __('Browse categories') }}</h2>
                <p>{{ __('Click a category to see what is inside it, then save.') }}</p>
            </header>

            <div class="catmodal__cols" data-category-columns>
                {{-- Columns are rendered by resources/js/verum/category-picker.js --}}
            </div>

            <footer class="catmodal__foot">
                <p class="catmodal__crumb" data-category-preview></p>
                <div class="catmodal__btns">
                    <button class="btn btn--ghost btn--sm" type="button" data-category-close>{{ __('Cancel') }}</button>
                    <button class="btn btn--ruby btn--sm" type="button" data-category-save disabled>{{ __('Save') }}</button>
                </div>
            </footer>
        </div>
    </div>
</div>
