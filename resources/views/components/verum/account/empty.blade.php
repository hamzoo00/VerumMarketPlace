@props([
    'icon' => 'gem',
    'title',
    'body' => null,
])

<div class="apanel__empty">
    <x-verum.icon :name="$icon" class="ic apanel__empty-ic" />
    <p><b>{{ $title }}</b></p>
    @if ($body)
        <p>{{ $body }}</p>
    @endif
</div>
