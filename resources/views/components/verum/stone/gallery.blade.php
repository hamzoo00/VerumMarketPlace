@props(['stone'])

@php
    $lot = $stone->auctionLot;
@endphp

<div class="gallery" data-reveal>
    <div class="gallery__stage plate" id="stage" data-s="{{ $stone->photoKey() === 'ruby' ? 1 : 1.2 }}">
        <x-verum.stone.photo
            :$stone
            large
            eager
            id="stageImg"
            :alt="__(':name, :ct, face up', ['name' => $stone->name, 'ct' => $stone->caratLabel()])"
        />

        <div class="loupe" id="loupe" aria-hidden="true"><span>10×</span></div>
        <span class="gallery__hint">
            <x-verum.icon name="loupe" /><span>{{ __('Hover to inspect') }}</span>
        </span>
        <span class="gallery__no">{{ $stone->report_number }}</span>

        <div class="gallery__badges">
            @if ($stone->tag)
                <span class="tag">{{ $stone->tag }}</span>
            @endif
            @if ($lot)
                <span class="tag tag--live"><i class="dot-live"></i>{{ __('Lot :n', ['n' => $lot->lot_number]) }}</span>
            @endif
        </div>
    </div>

    <div class="gallery__thumbs" role="group" aria-label="{{ __('Views') }}">
        <button class="gthumb is-on" type="button" data-view-mode="face" aria-pressed="true">
            <span class="plate"><x-verum.stone.photo :$stone size="160" /></span>
            <small>{{ __('Face up') }}</small>
        </button>
        <button class="gthumb" type="button" data-view-mode="detail" aria-pressed="false">
            <span class="plate gthumb__zoom"><x-verum.stone.photo :$stone size="160" /></span>
            <small>{{ __('Detail ×3') }}</small>
        </button>
        <button class="gthumb gthumb--cert" type="button" data-scrollto="#certSec">
            <span class="gthumb__doc">
                <svg class="brand__mark" aria-hidden="true"><use href="#vm-mark" /></svg>
                <i></i><i></i><i></i>
            </span>
            <small>{{ __('Report') }}</small>
        </button>
    </div>
</div>
