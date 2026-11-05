<?php

/*
|--------------------------------------------------------------------------
| Parcel tracking
|--------------------------------------------------------------------------
|
| Where a parcel is, from two sources: the courier's own scans, read
| through a tracking API, and the seller's updates. Both land in
| `shipment_events` and both are shown to the buyer, each marked with
| where it came from.
|
| The driver picks who answers App\Contracts\ParcelTracker:
|
|   fake     a local courier simulator — press "In transit", "Delivered"
|            on a test page. Refused in production.
|   17track  17TRACK's API (3,000+ carriers, national posts included,
|            which matters for parcels leaving Sri Lanka or Thailand).
|            Key in services.seventeentrack.
|   off      no courier API: the carrier's link and the seller's updates
|            only.
|
*/

return [

    'driver' => env('TRACKING_DRIVER', 'fake'),

    '17track' => [
        'url' => 'https://api.17track.net/track/v2.4',
    ],

    /*
    | The safety net for a missed webhook, and the only way updates arrive
    | where the provider cannot reach the app (a laptop). The provider
    | takes 40 numbers per request and 3 requests a second.
    */
    'sync' => [
        'batch' => 40,
        'max_per_run' => 400,
        'every_hours' => 4,
        'give_up_after_days' => 90,
    ],

    /*
    | One vocabulary for both sources, so a scan and a seller's update read
    | alike on the timeline.
    |
    | pick     when the seller may choose it: before dispatch, after, any
    |          time, or never (the app writes it — "posted" on dispatch,
    |          "delivered" from the button or a courier scan).
    | state    false when it is a remark rather than where the parcel is,
    |          so a note never becomes the parcel's status.
    | buyer    how the buyer hears of it: mail (email and the bell), bell,
    |          or not at all.
    | seller   the same for the seller, for a courier scan only — a
    |          seller is not told about their own update.
    | tone     the chip colour.
    |
    | Labels and headlines are translation strings; :reference is the
    | order.
    */
    'statuses' => [
        'preparing' => [
            'label' => 'Being prepared',
            'headline' => 'The seller is preparing :reference',
            'pick' => 'before', 'state' => true, 'buyer' => 'bell', 'seller' => null, 'tone' => null,
        ],
        'posted' => [
            'label' => 'Posted',
            'headline' => ':reference is on its way',
            'pick' => null, 'state' => true, 'buyer' => 'mail', 'seller' => null, 'tone' => 'on',
        ],
        'info_received' => [
            'label' => 'Carrier has the details',
            'headline' => 'The carrier has the details for :reference',
            'pick' => null, 'state' => true, 'buyer' => null, 'seller' => null, 'tone' => 'on',
        ],
        'in_transit' => [
            'label' => 'In transit',
            'headline' => ':reference is on the move',
            'pick' => 'after', 'state' => true, 'buyer' => 'bell', 'seller' => null, 'tone' => 'on',
        ],
        'customs' => [
            'label' => 'At customs',
            'headline' => ':reference is at customs',
            'pick' => 'after', 'state' => true, 'buyer' => 'bell', 'seller' => 'bell', 'tone' => 'warn',
        ],
        'delayed' => [
            'label' => 'Delayed',
            'headline' => ':reference is delayed',
            'pick' => 'after', 'state' => true, 'buyer' => 'mail', 'seller' => null, 'tone' => 'warn',
        ],
        'out_for_delivery' => [
            'label' => 'Out for delivery',
            'headline' => ':reference is out for delivery',
            'pick' => 'after', 'state' => true, 'buyer' => 'mail', 'seller' => null, 'tone' => 'on',
        ],
        'available_for_pickup' => [
            'label' => 'Ready to collect',
            'headline' => ':reference is ready to collect',
            'pick' => 'after', 'state' => true, 'buyer' => 'mail', 'seller' => 'bell', 'tone' => 'warn',
        ],
        'delivery_failed' => [
            'label' => 'Delivery attempt failed',
            'headline' => 'A delivery attempt for :reference failed',
            'pick' => 'after', 'state' => true, 'buyer' => 'mail', 'seller' => 'bell', 'tone' => 'warn',
        ],
        'exception' => [
            'label' => 'Problem with the parcel',
            'headline' => 'There is a problem with :reference',
            'pick' => 'after', 'state' => true, 'buyer' => 'mail', 'seller' => 'mail', 'tone' => 'off',
        ],
        'returned' => [
            'label' => 'Going back to the seller',
            'headline' => ':reference is going back to the seller',
            'pick' => 'after', 'state' => true, 'buyer' => 'mail', 'seller' => 'mail', 'tone' => 'off',
        ],
        'stalled' => [
            'label' => 'No recent scans',
            'headline' => ':reference has not been scanned for a while',
            'pick' => null, 'state' => true, 'buyer' => null, 'seller' => 'bell', 'tone' => 'warn',
        ],
        'delivered' => [
            'label' => 'Delivered',
            'headline' => ':reference was delivered',
            'pick' => null, 'state' => true, 'buyer' => 'mail', 'seller' => 'bell', 'tone' => 'ok',
        ],
        'retracked' => [
            'label' => 'Tracking number corrected',
            'headline' => 'New tracking number for :reference',
            'pick' => null, 'state' => false, 'buyer' => 'mail', 'seller' => null, 'tone' => null,
        ],
        'note' => [
            'label' => 'Note',
            'headline' => 'The seller wrote about :reference',
            'pick' => 'any', 'state' => false, 'buyer' => 'bell', 'seller' => null, 'tone' => null,
        ],
    ],

    // What a seller may write on an update.
    'limits' => [
        'description' => 300,
        'location' => 120,
    ],

];
