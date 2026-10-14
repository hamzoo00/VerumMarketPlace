@props([
    'name',
    'class' => 'ic',
])

{{-- Thin wrapper over the sprite: <x-verum.icon name="heart" />. --}}
<svg {{ $attributes->merge(['class' => $class]) }} aria-hidden="true">
    <use href="#i-{{ $name }}" />
</svg>
