@props([
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'full' => false,
])

{{-- Native select: works without JavaScript and is accessible by default. --}}
<div @class(['field', 'field--select', 'field--full' => $full, 'has-error' => $errors->has($name)])>
    <select
        id="f-{{ $name }}"
        name="{{ $name }}"
        @required($required)
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="e-{{ $name }}" @endif
        {{ $attributes }}
    >
        <option value="" @selected(old($name, $selected) === null || old($name, $selected) === '')>
            {{ $placeholder ?? '' }}
        </option>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) old($name, $selected) === (string) $value)>
                {{ $text }}
            </option>
        @endforeach
    </select>

    <label for="f-{{ $name }}">{{ $label }}</label>
    <x-verum.icon name="chev" class="ic field__chev" />

    @error($name)
        <p class="field__err" id="e-{{ $name }}" role="alert" data-server="true">{{ $message }}</p>
    @enderror
</div>
