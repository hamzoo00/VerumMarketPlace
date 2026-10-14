@props([
    'stone',
    'index' => 0,
])

@php
    $lot = $stone->auctionLot;
    $closed = $lot?->hasEnded() ?? false;
@endphp

<div class="cell" data-reveal style="--d:{{ min($index, 8) * 70 }}">
    <article
        @class(['scard', 'scard--lot' => $lot, 'is-closed' => $closed])
        style="--gem:{{ $stone->gemType->accent_color }}"
        data-tilt
        @if ($lot) data-lot="{{ $stone->slug }}" @endif
    >
        <div class="scard__in">
            <div class="scard__plate plate">
                <x-verum.stone.photo :$stone :alt="$stone->name.', '.$stone->caratLabel()" />

                <div class="scard__tags">
                    @if ($stone->tag)
                        <span class="tag">{{ $stone->tag }}</span>
                    @endif
                    @if ($lot)
                        <span class="tag tag--live"><i class="dot-live"></i>{{ __('Lot :n', ['n' => $lot->lot_number]) }}</span>
                    @endif
                </div>

                <x-verum.wish-button :$stone />

                <dl class="scard__specs">
                    <div><dt>{{ __('Weight') }}</dt><dd>{{ $stone->caratLabel() }}</dd></div>
                    <div><dt>{{ __('Cut') }}</dt><dd>{{ $stone->shape }}</dd></div>
                    <div><dt>{{ __('Treatment') }}</dt><dd>{{ $stone->treatmentShort() }}</dd></div>
                </dl>
            </div>

            <div class="scard__body">
                <p class="scard__meta">
                    <span>{{ $stone->gemType->label }}</span>
                    <span>{{ $stone->originLabel() }}</span>
                </p>

                <h3 class="scard__name">
                    <a href="{{ route('stones.show', $stone) }}" class="stretch" wire:navigate>{{ $stone->name }}</a>
                </h3>

                <div class="scard__foot">
                    @if ($lot)
                        <div class="scard__bid">
                            <span class="lbl">{{ __('Current bid') }}</span>
                            <strong data-cur="{{ $stone->slug }}">
                                {{ \App\Models\Money::fromCents($lot->current_bid_cents)->format() }}
                            </strong>
                        </div>
                        <x-verum.countdown :ends-at="$lot->ends_at" :slug="$stone->slug" />
                    @else
                        <strong class="scard__price">{{ $stone->price()?->format() }}</strong>
                        <button
                            class="scard__add"
                            type="button"
                            data-add="{{ $stone->slug }}"
                            aria-label="{{ __('Add :name to cart', ['name' => $stone->name]) }}"
                        >
                            <x-verum.icon name="plus" /><span>{{ __('Add') }}</span>
                        </button>
                    @endif
                </div>

                <p class="scard__cert">
                    <x-verum.icon name="shield" />
                    <span>{{ $stone->report_number }}</span>
                    <span class="scard__view">{{ __('View details') }}<x-verum.icon name="arrow" /></span>
                </p>
            </div>
        </div>
    </article>
</div>
