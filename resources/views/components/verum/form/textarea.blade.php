@props([
    'name',
    'label',
    'rows' => 4,
    'required' => false,
    'full' => true,
])

<div @class(['field', 'field--full' => $full, 'has-error' => $errors->has($name)])>
    <textarea
        id="f-{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder=" "
        @required($required)
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="e-{{ $name }}" @endif
        {{ $attributes }}
    >{{ old($name) }}</textarea>
    <label for="f-{{ $name }}">{{ $label }}</label>

    @error($name)
        <p class="field__err" id="e-{{ $name }}" role="alert">{{ $message }}</p>
    @enderror
</div>
