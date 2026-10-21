<?php

/*
|--------------------------------------------------------------------------
| Offers and coupons
|--------------------------------------------------------------------------
|
| Two separate promotions, both opt-in per seller.
|
| "Make an offer" follows the pattern every marketplace in this trade
| uses — eBay, Trade Me, Hip: a buyer names a price, the seller accepts,
| declines or counters, and an accepted offer is binding. Offers expire
| on their own so a listing is never held hostage by a stale negotiation.
|
| Coupons follow Etsy's shape: percent, fixed amount or free postage,
| with an optional minimum spend and expiry.
|
*/

return [

    'offers' => [
        // Hours an offer stays open. eBay and Trade Me both use 24-48;
        // this trade ships worldwide, so sellers get a little longer.
        'expires_after_hours' => (int) env('OFFER_EXPIRY_HOURS', 48),

        /*
        | How far below the asking price an offer may go. A derisory
        | offer wastes both sides' time, and every platform bounds it.
        */
        'min_percent_of_price' => (int) env('OFFER_MIN_PERCENT', 50),

        // Rounds of counter-offers before the negotiation must settle.
        'max_rounds' => 6,

        // Pending offers one buyer may hold on one listing.
        'max_open_per_buyer' => 1,
    ],

    'coupons' => [
        /*
        | Code shape. Etsy uses 5-20 alphanumerics; the same rule keeps a
        | code speakable over the phone and safe in a URL.
        */
        'code_min' => 5,
        'code_max' => 20,

        'types' => [
            'percent' => 'Percentage off',
            'fixed' => 'Amount off',
            'free_shipping' => 'Free postage',
        ],

        // A seller cannot give away more than this in one code.
        'max_percent' => 70,
        'max_fixed_cents' => 500_000,

        'max_active_per_seller' => 20,
    ],

];
