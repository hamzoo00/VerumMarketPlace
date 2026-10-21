@props([
    'name',
    // Array fields post as name[0][key] but are validated as name.0.key,
    // so the error lookup needs its own key.
    'errorKey' => null,
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
    // A short unit shown inside the control, e.g. "$" on a money field.
    'prefix' => null,
])

{{--
    Floating-label field.
    The data-* attributes drive resources/js/verum/validate.js; the server
    still validates everything independently.
--}}
<div @class(['field', 'field--full' => $full, 'field--prefixed' => $prefix !== null, 'has-error' => $errors->has($errorKey ?? $name)])>
    @if ($prefix)
        <span class="field__prefix" aria-hidden="true">{{ $prefix }}</span>
    @endif

    <input
        id="f-{{ $errorKey ?? $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        placeholder=" "
        value="{{ old($errorKey ?? $name, $value) }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @required($required)
        @if ($rule) data-rule="{{ $rule }}" @endif
        @if ($confirms) data-confirms="f-{{ $confirms }}" @endif
        @if ($minlength) data-minlength="{{ $minlength }}" @endif
        @if ($maxlength) data-maxlength="{{ $maxlength }}" maxlength="{{ $maxlength }}" @endif
        @if ($errors->has($errorKey ?? $name)) aria-invalid="true" aria-describedby="e-{{ $errorKey ?? $name }}" @endif
        {{ $attributes }}
    >
    <label for="f-{{ $errorKey ?? $name }}">{{ $label }}</label>

    @error($errorKey ?? $name)
        <p class="field__err" id="e-{{ $errorKey ?? $name }}" role="alert" data-server="true">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="field__hint">{{ $hint }}</p>
        @endif
    @enderror
</div>
