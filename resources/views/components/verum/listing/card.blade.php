@props([
    'listing',
    'index' => 0,
])

@php
    $isAuction = $listing->isAuction();
    $open = $isAuction && \App\Support\AuctionRules::isOpen($listing);
    $reserve = $isAuction ? \App\Support\AuctionRules::reserveLabel($listing) : null;
@endphp

<div class="cell" data-reveal data-live-slug="{{ $listing->slug }}" style="--d:{{ min($index, 8) * 70 }}">
    <article
        @class(['scard', 'scard--lot' => $isAuction, 'is-closed' => $isAuction && ! $open])
        data-tilt
        @if ($isAuction) data-lot="{{ $listing->slug }}" @endif
    >
        <div class="scard__in">
            <div class="scard__plate plate">
                <x-verum.listing.photo :$listing />

                <div class="scard__tags">
                    @if ($isAuction)
                        <span class="tag tag--live"><i class="dot-live"></i>{{ __('Auction') }}</span>
                    @endif
                    @if ($listing->untreated)
                        <span class="tag">{{ __('Untreated') }}</span>
                    @endif
                </div>

                <dl class="scard__specs">
                    @if ($listing->caratLabel())
                        <div><dt>{{ __('Weight') }}</dt><dd>{{ $listing->caratLabel() }}</dd></div>
                    @endif
                    @if ($listing->shape)
                        <div><dt>{{ __('Cut') }}</dt><dd>{{ $listing->shape }}</dd></div>
                    @endif
                    @if ($listing->origin)
                        <div><dt>{{ __('Origin') }}</dt><dd>{{ $listing->origin }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="scard__body">
                <p class="scard__meta">
                    <span>{{ $listing->category?->name }}</span>
                    <span>{{ $listing->seller?->name }}</span>
                </p>

                <h3 class="scard__name">
                    <a href="{{ route('listings.show', $listing) }}" class="stretch" wire:navigate>{{ $listing->title }}</a>
                </h3>

                <div class="scard__foot">
                    @if ($isAuction)
                        <div class="scard__bid">
                            <span class="lbl">{{ $listing->bids_count > 0 ? __('Current bid') : __('Opening bid') }}</span>
                            <strong>{{ $listing->currentBid()?->format() }}</strong>
                        </div>
                        <x-verum.countdown :ends-at="$listing->auction_ends_at" :slug="$listing->slug" />
                    @else
                        <strong class="scard__price">{{ $listing->price()?->format() }}</strong>
                    @endif
                </div>

                <p class="scard__cert">
                    @if ($listing->report_number)
                        <x-verum.icon name="shield" /><span>{{ $listing->report_number }}</span>
                    @elseif ($reserve)
                        <x-verum.icon name="info" /><span>{{ $reserve }}</span>
                    @else
                        <x-verum.icon name="gem" /><span>{{ $listing->lab ?? __('Seller listed') }}</span>
                    @endif
                    <span class="scard__view">{{ __('View details') }}<x-verum.icon name="arrow" /></span>
                </p>
            </div>
        </div>
    </article>
</div>
