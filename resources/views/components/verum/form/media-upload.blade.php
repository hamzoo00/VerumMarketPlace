@props([
    'listing',
    'kind' => 'images',
])

@php
    $config = config("verum_media.{$kind}");
    $existing = $listing->getMedia($config['collection']);
    $maxMb = round($config['max_kb'] / 1024);
    $isVideo = $kind === 'videos';
@endphp

{{--
    Upload widget: drop or choose files, see each one's progress, preview
    what landed, remove anything unwanted. One request per file, so a
    rejection affects only that file.
--}}
<div
    class="upload"
    data-upload
    data-kind="{{ $kind }}"
    data-endpoint="{{ route('seller.listings.media.store', $listing) }}"
    data-max-kb="{{ $config['max_kb'] }}"
    data-max-files="{{ $config['max_files'] }}"
    data-accept="{{ implode(',', $config['mimes']) }}"
    data-count="{{ $existing->count() }}"
>
    <div class="upload__drop" data-upload-drop>
        <x-verum.icon :name="$isVideo ? 'eye' : 'upload'" class="ic upload__ic" />

        <p class="upload__t">
            {{ $isVideo ? __('Add a video') : __('Add photos') }}
        </p>

        <p class="upload__hint">
            {{ __(':formats · up to :mb MB each · :count max', [
                'formats' => mb_strtoupper(implode(', ', $config['extensions'])),
                'mb' => $maxMb,
                'count' => $config['max_files'],
            ]) }}
        </p>

        <label class="btn btn--ghost btn--sm">
            {{ __('Choose files') }}
            <input
                type="file"
                class="sr-only"
                accept="{{ implode(',', $config['mimes']) }}"
                @unless ($isVideo) multiple @endunless
                data-upload-input
            >
        </label>
    </div>

    <ul class="upload__queue" data-upload-queue aria-live="polite"></ul>

    <ul class="upload__items" data-upload-items>
        @foreach ($existing as $media)
            <li class="umedia" data-media-id="{{ $media->id }}">
                @if ($isVideo)
                    <video class="umedia__v" src="{{ $media->getUrl() }}" controls preload="metadata"></video>
                @else
                    <img
                        class="umedia__img"
                        src="{{ $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl() }}"
                        alt="{{ $media->file_name }}"
                        loading="lazy"
                    >
                @endif

                <button
                    class="umedia__x"
                    type="button"
                    data-media-remove="{{ route('seller.listings.media.destroy', [$listing, $media]) }}"
                    aria-label="{{ __('Remove :name', ['name' => $media->file_name]) }}"
                >
                    <x-verum.icon name="close" />
                </button>
            </li>
        @endforeach
    </ul>
</div>
