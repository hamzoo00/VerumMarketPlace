@props([
    'name',
    'legend',
    'options' => [],
    'selected' => null,
    'model' => null,
])

{{-- One choice per row, so long option lists stay scannable. --}}
<fieldset class="fgrp">
    <legend class="lbl">{{ $legend }}</legend>

    <div class="chklist">
        @foreach ($options as $value => $label)
            <label class="chk">
                <input
                    type="radio"
                    value="{{ $value }}"
                    @if ($model)
                        wire:model.live="{{ $model }}"
                    @else
                        name="{{ $name }}"
                        @checked((string) $selected === (string) $value)
                    @endif
                >
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>
</fieldset>
