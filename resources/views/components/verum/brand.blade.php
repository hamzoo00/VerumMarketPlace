@props([
    'href' => null,
    'class' => 'brand',
    'label' => null,
])

@php
    $target = $href ?? route('home');
@endphp

<a class="{{ $class }}" href="{{ $target }}" aria-label="{{ $label ?? __(':app, home', ['app' => config('app.name')]) }}" wire:navigate>
    <svg class="brand__mark" aria-hidden="true"><use href="#vm-mark" /></svg>
    <svg class="brand__word" aria-hidden="true"><use href="#vm-word" /></svg>
</a>
