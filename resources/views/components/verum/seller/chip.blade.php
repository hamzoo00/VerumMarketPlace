@props([
    'seller',
    'reveal' => true,
])

<a class="sellerchip" href="{{ route('sellers.show', $seller) }}" @if ($reveal) data-reveal @endif wire:navigate>
    <x-verum.seller.crest :$seller size="sm" />
    <span class="sellerchip__t">
        <b>{{ $seller->name }}</b>
        <small>
            {{ $seller->location }} · {{ number_format($seller->rating, 1) }} ★ ·
            {{ trans_choice(':count review|:count reviews', $seller->reviews_count) }}
        </small>
    </span>
    <span class="vbadge"><x-verum.icon name="shield" />{{ __('Verified') }}</span>
    <x-verum.icon name="arrow" class="ic sellerchip__go" />
</a>
