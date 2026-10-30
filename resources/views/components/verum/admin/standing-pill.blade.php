@props(['account'])

@php
    use App\Models\SellerAccount;

    $standing = $account->standing();
    $tone = match ($standing) {
        SellerAccount::STANDING_ACTIVE => 'pill--ok',
        SellerAccount::STANDING_SUSPENDED, SellerAccount::STANDING_CLOSING => 'pill--hold',
        SellerAccount::STANDING_CLOSED => 'pill--bad',
        default => 'pill--muted',
    };
@endphp

<span class="pill {{ $tone }}">{{ SellerAccount::standingLabels()[$standing] }}</span>

@if ($standing === SellerAccount::STANDING_CLOSING && $account->closes_at)
    {{-- When it will close, because buyers' parcels are still on the way. --}}
    <small>{{ __('until :date', ['date' => $account->closes_at->format('j M Y')]) }}</small>
@elseif ($standing === SellerAccount::STANDING_SUSPENDED && $account->suspended_at)
    <small>{{ __('since :date', ['date' => $account->suspended_at->format('j M Y')]) }}</small>
@endif
