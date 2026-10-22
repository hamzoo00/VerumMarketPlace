@props(['stone'])

@php
    $lot = $stone->auctionLot;
    $seller = $stone->seller;
@endphp

<div class="buy">
    <p class="kicker" data-reveal>
        {{ $stone->gemType->label }} — {{ __('natural') }}, {{ mb_strtolower($stone->treatmentShort()) }}
    </p>

    <h1 class="buy__title" data-split>
        {{ $stone->name }}, <em>{{ $stone->caratLabel() }}</em>
    </h1>

    @if (! $lot)
        <p class="buy__price" data-reveal>
            {{ $stone->price()?->withLocal() }}
            <small>{{ __(':currency · insured shipping quoted at checkout', ['currency' => $stone->currency]) }}</small>
        </p>
    @endif

    <p class="buy__desc" data-reveal>{{ $stone->description }}</p>

    @if ($lot)
        <x-verum.auction.bid-box :$lot />
    @endif

    <x-verum.stone.specs :$stone />

    @unless ($lot)
        <div class="buy__actions" id="buyActions" data-reveal>
            <p class="oneof">
                <x-verum.icon name="gem" />
                <span><b>{{ __('One of one.') }}</b> {{ __('This exact stone, report :report.', ['report' => $stone->report_number]) }}</span>
            </p>
            <div class="buy__btns">
                <button class="btn btn--ruby btn--lg" type="button" data-add="{{ $stone->slug }}" data-magnetic>
                    {{ __('Add to cart') }}
                    <span class="arr" aria-hidden="true"><svg><use href="#i-arrow" /></svg><svg><use href="#i-arrow" /></svg></span>
                </button>
                <a class="btn btn--ghost btn--lg" href="{{ route('contact') }}" wire:navigate>
                    <x-verum.icon name="mail" />{{ __('Ask about this stone') }}
                </a>
                <button
                    class="ibtn ibtn--line ibtn--lg wish"
                    type="button"
                    data-wish="{{ $stone->slug }}"
                    aria-pressed="false"
                    aria-label="{{ __('Save this stone') }}"
                >
                    <x-verum.icon name="heart" />
                </button>
            </div>
        </div>
    @endunless

    <ul class="assure" data-reveal>
        <li><x-verum.icon name="scale" /><span><b>{{ __('Escrow') }}</b>{{ __('The seller is paid after you confirm') }}</span></li>
        <li><x-verum.icon name="box" /><span><b>{{ __('Insured shipping') }}</b>{{ __('Tracked, door to door') }}</span></li>
        <li><x-verum.icon name="return" /><span><b>{{ __('14-day returns') }}</b>{{ __("Full refund if it doesn't match") }}</span></li>
    </ul>

    <x-verum.seller.chip :$seller />
</div>
