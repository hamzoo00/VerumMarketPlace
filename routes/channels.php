<?php

use App\Models\Buyer;
use App\Models\SellerAccount;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Offer channels
|--------------------------------------------------------------------------
| A negotiation is private to its two parties, so each side may only
| listen on its own channel. The guard is what authorises, never the id
| in the channel name.
*/
Broadcast::channel('buyer.{buyerId}.offers', function ($user, int $buyerId): bool {
    return $user instanceof Buyer && (int) $user->id === $buyerId;
}, ['guards' => ['buyer']]);

Broadcast::channel('seller.{sellerId}.offers', function ($user, int $sellerId): bool {
    return $user instanceof SellerAccount
        && (int) ($user->storefront?->id) === $sellerId;
}, ['guards' => ['seller']]);
