@props([
    'name' => 'phone',
    'label' => null,
    'dialName' => 'dial_code',
    'countryField' => 'f-country',
    'required' => false,
])

@php
    $dialOptions = \App\Support\Countries::dialOptions();
    $currentDial = old($dialName);
@endphp

{{--
    Dialling code plus number. The code pre-fills from the country select
    (see resources/js/verum/phone.js) but stays editable, because a seller's
    phone is not always registered in the country they trade from.
    Submitted as two fields and joined server-side into one E.164 string.
--}}
<div
    @class(['field', 'field--phone', 'field--full', 'has-error' => $errors->has($name)])
    data-phone-field
    data-country-source="{{ $countryField }}"
>
    <div class="phone">
        <label class="sr-only" for="f-{{ $dialName }}">{{ __('Country calling code') }}</label>
        <select id="f-{{ $dialName }}" name="{{ $dialName }}" class="phone__dial" data-phone-dial>
            @foreach ($dialOptions as $dial => $text)
                <option value="{{ $dial }}" @selected($currentDial === $dial)>{{ $text }}</option>
            @endforeach
        </select>

        <input
            id="f-{{ $name }}"
            name="{{ $name }}"
            type="tel"
            class="phone__number"
            inputmode="tel"
            autocomplete="tel-national"
            placeholder=" "
            value="{{ old($name) }}"
            @required($required)
            data-maxlength="20"
            @if ($errors->has($name)) aria-invalid="true" aria-describedby="e-{{ $name }}" @endif
        >
        <label class="phone__label" for="f-{{ $name }}">{{ $label ?? __('Phone number') }}</label>
    </div>

    @error($name)
        <p class="field__err" id="e-{{ $name }}" role="alert" data-server="true">{{ $message }}</p>
    @enderror
    @error($dialName)
        <p class="field__err" role="alert" data-server="true">{{ $message }}</p>
    @enderror
</div>
