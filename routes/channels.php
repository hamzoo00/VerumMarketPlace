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
| Notification channels
|--------------------------------------------------------------------------
| Each account hears only its own bell. The area is part of the name
| because a buyer and a seller account may share an id; the guard and
| the model type are what authorise, never the number in the channel.
*/
Broadcast::channel('notifications.buyer.{id}', function ($user, int $id): bool {
    return $user instanceof Buyer && (int) $user->id === $id;
}, ['guards' => ['buyer']]);

Broadcast::channel('notifications.seller.{id}', function ($user, int $id): bool {
    return $user instanceof SellerAccount && (int) $user->id === $id && ! $user->isClosed();
}, ['guards' => ['seller']]);

Broadcast::channel('notifications.admin.{id}', function ($user, int $id): bool {
    return $user instanceof Admin && (int) $user->id === $id && $user->canAccessDashboard();
}, ['guards' => ['admin']]);

/*
| Staff pages listen here to re-read whatever another admin just changed.
| The payload names areas only, so nothing private crosses it anyway.
*/
Broadcast::channel('staff', function ($user): bool {
    return $user instanceof Admin && $user->canAccessDashboard();
}, ['guards' => ['admin']]);

/*
| Seller pages listen here for an admin's Settings save, so a switch like
| listing approval changes their buttons without a reload. Section names
| only, like the staff channel.
*/
Broadcast::channel('platform.sellers', function ($user): bool {
    return $user instanceof SellerAccount && $user->canAccessDashboard();
}, ['guards' => ['seller']]);

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
