<?php

namespace App\Actions\Chat;

use App\Models\Buyer;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Seller;

/**
 * Finds the thread between a buyer and a seller, or opens one.
 *
 * `firstOrCreate` on the unique index rather than a check-then-insert:
 * two tabs opening the same conversation would otherwise race and
 * split the history in two.
 */
class StartConversation
{
    public function handle(Buyer $buyer, Seller $seller, ?Listing $listing = null): Conversation
    {
        return Conversation::firstOrCreate(
            [
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'listing_id' => $listing?->id,
            ],
            [
                'subject' => $listing?->title,
                'last_message_at' => now(),
            ],
        );
    }
}
