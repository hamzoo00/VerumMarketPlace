@props([
    'title',
    'hint' => null,
    'icon' => null,
])

{{--
    Placeholder panel for the dashboards.
    Real content replaces the slot as each feature is built.
--}}
<article class="apanel" data-reveal>
    <header class="apanel__head">
        @if ($icon)
            <span class="apanel__ic"><x-verum.icon :name="$icon" /></span>
        @endif
        <div>
            <h2 class="apanel__t">{{ $title }}</h2>
            @if ($hint)
                <p class="apanel__hint">{{ $hint }}</p>
            @endif
        </div>
    </header>

    <div class="apanel__body">
        {{ $slot }}
    </div>
</article>
