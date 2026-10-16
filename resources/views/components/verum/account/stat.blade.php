@props([
    'label',
    'value',
    'hint' => null,
])

<li class="astat">
    <b>{{ $value }}</b>
    <span>{{ $label }}</span>
    @if ($hint)
        <small>{{ $hint }}</small>
    @endif
</li>
