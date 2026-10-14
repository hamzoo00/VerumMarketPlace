@props(['stone'])

{{-- Saved state lives client-side until buyer accounts land. --}}
<button
    class="wish"
    type="button"
    data-wish="{{ $stone->slug }}"
    aria-pressed="false"
    aria-label="{{ __('Save :name', ['name' => $stone->name]) }}"
>
    <x-verum.icon name="heart" />
</button>
