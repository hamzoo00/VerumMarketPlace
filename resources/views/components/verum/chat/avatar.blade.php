@props([
    'conversation',
    'viewer',
])

@php
    use App\Models\Message;

    /*
     * Only a seller has an uploaded photograph, so the crest is used
     * where it exists and initials stand in everywhere else — one shape
     * either way, so the list does not jump between row heights.
     */
    $seller = $conversation->seller;
    $showSellerPhoto = $viewer !== Message::FROM_SELLER && $seller?->avatarUrl() !== null;
@endphp

@if ($showSellerPhoto)
    <x-verum.seller.crest :seller="$seller" size="sm" />
@else
    <span class="tavatar" aria-hidden="true">{{ $conversation->counterpartInitials($viewer) }}</span>
@endif
