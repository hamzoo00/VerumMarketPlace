@props([
    'name',
    // Array fields post as name[0][key] but are validated as name.0.key,
    // so the error lookup needs its own key.
    'errorKey' => null,
    'label',
    // value => label, or group label => [value => label] for <optgroup>s.
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'full' => false,
    'hint' => null,
])

@php
    $key = $errorKey ?? $name;
    $current = (string) old($key, $selected);

    // A stored value the list no longer offers is shown, marked, rather
    // than silently swapped for the placeholder — saving then says what
    // to change instead of quietly clearing it.
    $known = collect($options)
        ->flatMap(fn ($text, $value) => is_array($text) ? array_keys($text) : [$value])
        ->map(fn ($value): string => (string) $value);
    $stray = $current !== '' && ! $known->contains($current);
@endphp

{{-- Native select: works without JavaScript and is accessible by default. --}}
<div @class(['field', 'field--select', 'field--full' => $full, 'has-error' => $errors->has($key)])>
    <select
        id="f-{{ $key }}"
        name="{{ $name }}"
        @required($required)
        @if ($errors->has($key)) aria-invalid="true" aria-describedby="e-{{ $key }}" @endif
        {{ $attributes }}
    >
        <option value="" @selected($current === '')>
            {{ $placeholder ?? '' }}
        </option>
        @if ($stray)
            <option value="{{ $current }}" selected>{{ __(':value (not on the list)', ['value' => $current]) }}</option>
        @endif
        @foreach ($options as $value => $text)
            @if (is_array($text))
                <optgroup label="{{ $value }}">
                    @foreach ($text as $groupValue => $groupText)
                        <option value="{{ $groupValue }}" @selected($current === (string) $groupValue)>{{ $groupText }}</option>
                    @endforeach
                </optgroup>
            @else
                <option value="{{ $value }}" @selected($current === (string) $value)>
                    {{ $text }}
                </option>
            @endif
        @endforeach
    </select>

    <label for="f-{{ $key }}">{{ $label }}</label>
    <x-verum.icon name="chev" class="ic field__chev" />

    @error($key)
        <p class="field__err" id="e-{{ $key }}" role="alert" data-server="true">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="field__hint">{{ $hint }}</p>
        @endif
    @enderror
</div>
