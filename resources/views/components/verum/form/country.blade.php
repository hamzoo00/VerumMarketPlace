@props([
    'name' => 'country',
    'label' => null,
    'selected' => null,
    'required' => false,
    'full' => false,
])

@php
    $countries = \App\Support\Countries::all();
    $current = old($name, $selected);
@endphp

{{--
    Country select. Each option carries its dialling code so the phone field
    can pre-fill the prefix without a second lookup.
--}}
<div @class(['field', 'field--select', 'field--full' => $full, 'has-error' => $errors->has($name)])>
    <select
        id="f-{{ $name }}"
        name="{{ $name }}"
        @required($required)
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="e-{{ $name }}" @endif
        {{ $attributes }}
    >
        <option value=""></option>
        @foreach ($countries as $iso => $country)
            <option value="{{ $iso }}" data-dial="{{ $country['dial'] }}" @selected((string) $current === (string) $iso)>
                {{ $country['name'] }}
            </option>
        @endforeach
    </select>

    <label for="f-{{ $name }}">{{ $label ?? __('Country / region') }}</label>
    <x-verum.icon name="chev" class="ic field__chev" />

    @error($name)
        <p class="field__err" id="e-{{ $name }}" role="alert" data-server="true">{{ $message }}</p>
    @enderror
</div>
