@props(['listing'])

@php
    $shots = $listing->imageUrls('');
    $videos = $listing->videos();
    $count = count($shots);
@endphp

{{--
    Server-rendered slides: a crawler sees every photograph, and the arrows
    only reorder what is already on the page.
--}}
<div class="gallery" data-reveal @if ($count > 1) data-gallery @endif>
    <div class="gallery__stage plate">
        @if ($count > 0)
            @foreach ($shots as $i => $src)
                <img
                    @class(['gallery__img', 'is-on' => $i === 0])
                    src="{{ $src }}"
                    alt="{{ $i === 0 ? $listing->title : __(':title — view :n', ['title' => $listing->title, 'n' => $i + 1]) }}"
                    loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                    decoding="async"
                    draggable="false"
                >
            @endforeach
        @else
            <x-verum.listing.photo :$listing size="hero" eager />
        @endif

        @if ($count > 1)
            <button class="gnav gnav--prev" type="button" data-gallery-prev aria-label="{{ __('Previous image') }}">
                <x-verum.icon name="arrow" />
            </button>
            <button class="gnav gnav--next" type="button" data-gallery-next aria-label="{{ __('Next image') }}">
                <x-verum.icon name="arrow" />
            </button>

            <p class="gallery__no" data-gallery-count aria-live="polite">1 / {{ $count }}</p>
        @endif
    </div>

    @if ($count > 1)
        <div class="gallery__thumbs" role="tablist" aria-label="{{ __('Images') }}">
            @foreach ($shots as $i => $src)
                <button
                    @class(['gthumb', 'is-on' => $i === 0])
                    type="button"
                    role="tab"
                    data-gallery-go="{{ $i }}"
                    aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                    aria-label="{{ __('View image :n', ['n' => $i + 1]) }}"
                >
                    <span class="plate"><img src="{{ $src }}" alt="" loading="lazy" decoding="async"></span>
                </button>
            @endforeach
        </div>
    @endif

    @if ($videos->isNotEmpty())
        <div class="gallery__videos">
            @foreach ($videos as $video)
                <video class="gallery__video" controls preload="metadata" playsinline>
                    <source src="{{ $video->getUrl() }}" type="{{ $video->mime_type }}">
                    {{ __('Your browser cannot play this video.') }}
                </video>
            @endforeach
        </div>
    @endif
</div>
