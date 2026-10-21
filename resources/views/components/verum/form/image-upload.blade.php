@props([
    'kind',
    'label',
    'hint' => null,
    'current' => null,
])

@php
    $config = config("verum_media.{$kind}");
    $maxMb = round($config['max_kb'] / 1024);
@endphp

{{--
    One image, replaced on upload. Limits come from config and are enforced
    in the browser *and* on the server — the client check only saves a
    pointless round trip with a 10 MB file.
--}}
<div
    @class(['imgup', "imgup--{$kind}"])
    data-image-upload="{{ $kind }}"
    data-url="{{ route('seller.branding.store') }}"
    data-max-kb="{{ $config['max_kb'] }}"
    data-accept="{{ implode(',', $config['mimes']) }}"
>
    <span class="lbl">{{ $label }}</span>

    <div class="imgup__row">
        <div class="imgup__preview" data-image-preview>
            @if ($current)
                <img src="{{ $current }}" alt="">
            @else
                <span class="imgup__placeholder" aria-hidden="true">
                    <x-verum.icon name="upload" />
                </span>
            @endif
        </div>

        <div class="imgup__ctrl">
            <label class="btn btn--ghost btn--sm">
                <input
                    type="file"
                    class="sr-only"
                    accept="{{ implode(',', $config['mimes']) }}"
                    data-image-input
                >
                {{ $current ? __('Replace') : __('Upload') }}
            </label>

            <button
                class="btn btn--ghost btn--sm"
                type="button"
                data-image-remove
                @unless ($current) hidden @endunless
            >{{ __('Remove') }}</button>

            <p class="imgup__hint">
                {{ $hint }}
                {{ __('Up to :mb MB · :formats', [
                    'mb' => $maxMb,
                    'formats' => mb_strtoupper(implode(', ', $config['extensions'])),
                ]) }}
            </p>

            <div class="imgup__bar" data-image-bar hidden><i></i></div>
        </div>
    </div>
</div>
