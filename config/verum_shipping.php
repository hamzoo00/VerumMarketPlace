<?php

/*
|--------------------------------------------------------------------------
| Shipping
|--------------------------------------------------------------------------
|
| Gem marketplaces do not integrate carriers: a seller posts a small parcel
| from wherever they are, so the platform collects a flat price per service
| and a quoted transit window instead of live rates.
|
| Sellers set defaults once, then a listing may override them. Both paths
| read the services and destinations below, so a name never drifts.
|
*/

return [

    /*
    | The services a seller may offer. `key` is stored; the label is shown.
    | Keeping these fixed means a buyer comparing two sellers compares like
    | with like, which free-text service names would not allow.
    */
    'services' => [
        'standard' => [
            'label' => 'Standard',
            'hint' => 'Tracked post, the usual choice.',
            'default_days' => [7, 21],
        ],
        'express' => [
            'label' => 'Express',
            'hint' => 'Courier or express post, signature on delivery.',
            'default_days' => [3, 7],
        ],
        'economy' => [
            'label' => 'Economy',
            'hint' => 'Cheapest post. Often untracked — say so.',
            'default_days' => [14, 40],
        ],
        'pickup' => [
            'label' => 'Local pickup',
            'hint' => 'Collected in person. No postage charged.',
            'default_days' => [0, 0],
        ],
    ],

    /*
    | Where a rate applies. Two zones match how these parcels actually move
    | and keep the form short; a per-country matrix is a later problem.
    */
    'destinations' => [
        'domestic' => 'Within my country',
        'international' => 'Rest of the world',
    ],

    /*
    | Dispatch — the working days between payment and handover to the
    | carrier. This is the seller's own promise and the part they control;
    | transit time afterwards is the carrier's.
    */
    'handling' => [
        'default_days' => 2,
        'max_days' => 30,
    ],

    /*
    | Guard rails. Prices are integer minor units everywhere.
    */
    'limits' => [
        'max_price_cents' => 500_000,
        'max_transit_days' => 120,
        'max_rates_per_seller' => 12,
    ],

    /*
    | Carriers a seller picks when marking an order dispatched. The buyer
    | gets a "Track parcel" link to `url` with the number filled in
    | (App\Support\Carriers), and the number is also given to PayPal under
    | `paypal`, which protects the seller if a buyer says it never came.
    |
    | Carriers without a reliable tracking page of their own go to
    | 17TRACK, which recognises the carrier from the number itself.
    */
    'carriers' => [
        'dhl' => ['name' => 'DHL Express', 'paypal' => 'DHL', 'url' => 'https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id=:number'],
        'fedex' => ['name' => 'FedEx', 'paypal' => 'FEDEX', 'url' => 'https://www.fedex.com/fedextrack/?trknbr=:number'],
        'ups' => ['name' => 'UPS', 'paypal' => 'UPS', 'url' => 'https://www.ups.com/track?tracknum=:number'],
        'usps' => ['name' => 'USPS', 'paypal' => 'USPS', 'url' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=:number'],
        'royal_mail' => ['name' => 'Royal Mail', 'paypal' => 'OTHER', 'url' => 'https://www.royalmail.com/track-your-item#/tracking-results/:number'],
        'aramex' => ['name' => 'Aramex', 'paypal' => 'ARAMEX', 'url' => 'https://t.17track.net/en#nums=:number'],
        'tnt' => ['name' => 'TNT', 'paypal' => 'TNT', 'url' => 'https://t.17track.net/en#nums=:number'],
        'ems' => ['name' => 'EMS / national post', 'paypal' => 'OTHER', 'url' => 'https://t.17track.net/en#nums=:number'],
        'other' => ['name' => 'Another carrier', 'paypal' => 'OTHER', 'url' => 'https://t.17track.net/en#nums=:number'],
    ],

    // Letters, digits, spaces and dashes — every carrier's numbers fit.
    'tracking_pattern' => '[A-Za-z0-9 \\-]{6,40}',

];
