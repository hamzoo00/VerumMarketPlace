@props([
    'categoryName' => 'category',
    'name' => 'specialties',
    'selected' => [],
])

@php
    $categories = \App\Support\SellerCategories::all();
    $chosen = (array) old($name, $selected);
@endphp

{{--
    Specialties grouped by category. Every group is rendered once and the
    irrelevant ones are hidden, so the choice survives a validation redisplay
    and still works with JavaScript off (all groups simply show).
--}}
<fieldset
    class="field field--full field--choice"
    data-specialties
    data-category-source="f-{{ $categoryName }}"
>
    <legend>{{ __('What do you primarily sell?') }}</legend>

    @foreach ($categories as $key => $category)
        @continue($key === \App\Support\SellerCategories::BOTH)

        <div class="choicegroup" data-specialty-group="{{ $key }}">
            <p class="choicegroup__t">{{ $category['label'] }}</p>
            <div class="choice choice--multi">
                @foreach ($category['specialties'] as $value => $label)
                    <label>
                        <input
                            type="checkbox"
                            name="{{ $name }}[]"
                            value="{{ $value }}"
                            @checked(in_array($value, $chosen, true))
                        >
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach

    @error($name)
        <p class="field__err" role="alert" data-server="true">{{ $message }}</p>
    @enderror
    @error($name.'.*')
        <p class="field__err" role="alert" data-server="true">{{ $message }}</p>
    @enderror
</fieldset>
