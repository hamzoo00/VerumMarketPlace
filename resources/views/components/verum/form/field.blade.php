@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'autocomplete' => null,
    'full' => false,
    'hint' => null,
    'rule' => null,
    'confirms' => null,
    'minlength' => null,
    'maxlength' => null,
])

{{--
    Floating-label field.
    The data-* attributes drive resources/js/verum/validate.js; the server
    still validates everything independently.
--}}
<div @class(['field', 'field--full' => $full, 'has-error' => $errors->has($name)])>
    <input
        id="f-{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        placeholder=" "
        value="{{ old($name, $value) }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @required($required)
        @if ($rule) data-rule="{{ $rule }}" @endif
        @if ($confirms) data-confirms="f-{{ $confirms }}" @endif
        @if ($minlength) data-minlength="{{ $minlength }}" @endif
        @if ($maxlength) data-maxlength="{{ $maxlength }}" maxlength="{{ $maxlength }}" @endif
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="e-{{ $name }}" @endif
        {{ $attributes }}
    >
    <label for="f-{{ $name }}">{{ $label }}</label>

    @error($name)
        <p class="field__err" id="e-{{ $name }}" role="alert" data-server="true">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="field__hint">{{ $hint }}</p>
        @endif
    @enderror
</div>
