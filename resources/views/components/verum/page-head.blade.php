@props([
    'title',
    'emphasis' => null,
    'lede' => null,
    'crumbs' => [],
])

{{-- Shared page header with breadcrumbs. $crumbs is [label => url|null]. --}}
<header class="phead wrap">
    @if ($crumbs !== [])
        <nav class="crumbs" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</a>
            @foreach ($crumbs as $label => $url)
                <span aria-hidden="true">/</span>
                @if ($url)
                    <a href="{{ $url }}" wire:navigate>{{ $label }}</a>
                @else
                    <span aria-current="page">{{ $label }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    <div class="phead__row">
        <h1 class="phead__title" data-split>
            {{ $title }}@if ($emphasis) <em>{{ $emphasis }}</em>@endif
        </h1>
        @if ($lede)
            <p class="phead__lede" data-reveal>{{ $lede }}</p>
        @endif
    </div>

    {{ $slot ?? '' }}
</header>
