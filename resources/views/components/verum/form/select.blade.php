@props([
    'name',
    // Array fields post as name[0][key] but are validated as name.0.key,
    // so the error lookup needs its own key.
    'errorKey' => null,
    'label',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'full' => false,
])

{{-- Native select: works without JavaScript and is accessible by default. --}}
<div @class(['field', 'field--select', 'field--full' => $full, 'has-error' => $errors->has($errorKey ?? $name)])>
    <select
        id="f-{{ $errorKey ?? $name }}"
        name="{{ $name }}"
        @required($required)
        @if ($errors->has($errorKey ?? $name)) aria-invalid="true" aria-describedby="e-{{ $errorKey ?? $name }}" @endif
        {{ $attributes }}
    >
        <option value="" @selected(old($errorKey ?? $name, $selected) === null || old($errorKey ?? $name, $selected) === '')>
            {{ $placeholder ?? '' }}
        </option>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) old($errorKey ?? $name, $selected) === (string) $value)>
                {{ $text }}
            </option>
        @endforeach
    </select>

    <label for="f-{{ $errorKey ?? $name }}">{{ $label }}</label>
    <x-verum.icon name="chev" class="ic field__chev" />

    @error($errorKey ?? $name)
        <p class="field__err" id="e-{{ $errorKey ?? $name }}" role="alert" data-server="true">{{ $message }}</p>
    @enderror
</div>
