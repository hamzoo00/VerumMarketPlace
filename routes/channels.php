<?php

use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Conversation;
use App\Models\Message;
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

/*
|--------------------------------------------------------------------------
| Conversation channel
|--------------------------------------------------------------------------
| A thread is private to its two parties, plus any admin. Membership is
| decided by Conversation::isParticipant() — the same method the
| controllers use, so the socket can never be laxer than the page.
*/
Broadcast::channel('conversation.{id}', function ($user, int $id): bool {
    $conversation = Conversation::find($id);

    if ($conversation === null) {
        return false;
    }

    return match (true) {
        $user instanceof Buyer => $conversation->isParticipant(
            Message::FROM_BUYER,
            (int) $user->id,
        ),
        // An unapproved seller has no storefront, so no thread is theirs.
        $user instanceof SellerAccount => $user->storefront !== null
            && $conversation->isParticipant(Message::FROM_SELLER, (int) $user->storefront->id),
        $user instanceof Admin => true,
        default => false,
    };
}, ['guards' => ['buyer', 'seller', 'admin']]);
