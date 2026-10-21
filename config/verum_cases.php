<?php

/*
|--------------------------------------------------------------------------
| Disputes and audits
|--------------------------------------------------------------------------
|
| Two things a buyer can raise, settled by an admin in one queue.
|
|   dispute — a problem with a seller they dealt with
|   audit   — "this listing looks wrong", from any signed-in buyer
|
| The reasons are fixed rather than free text so the admin queue can be
| filtered and a pattern across sellers is visible.
|
*/

return [

    'disputes' => [
        'reasons' => [
            'not_as_described' => 'Not as described',
            'not_received' => 'Never arrived',
            'damaged' => 'Arrived damaged',
            'wrong_item' => 'Wrong item sent',
            'no_response' => 'Seller will not reply',
            'other' => 'Something else',
        ],
    ],

    'audits' => [
        'reasons' => [
            'misdescribed' => 'Treatment or origin looks wrong',
            'fake' => 'Suspected synthetic or fake',
            'stolen_photos' => 'Photographs are not the seller’s',
            'prohibited' => 'Should not be sold here',
            'pricing' => 'Misleading price or shipping',
            'other' => 'Something else',
        ],
    ],

    /*
    | What an admin can decide. Both parties are told either way, so the
    | wording is what they will read.
    */
    'resolutions' => [
        'upheld' => 'Upheld — action taken',
        'seller_warned' => 'Seller warned',
        'listing_removed' => 'Listing removed',
        'refund_due' => 'Refund due to buyer',
        'dismissed' => 'No action needed',
        'insufficient' => 'Not enough information',
    ],

    'limits' => [
        // Open cases one buyer may hold, so the queue cannot be flooded.
        'max_open_per_buyer' => 10,
        // The same buyer may not re-report the same listing.
        'body_max' => 2000,
    ],

];
