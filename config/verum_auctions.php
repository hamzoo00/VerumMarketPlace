<?php

/*
|--------------------------------------------------------------------------
| Auction rules
|--------------------------------------------------------------------------
|
| The house rules every lot runs under. Kept in config so they can be tuned
| without touching the rules engine.
|
*/

return [

    /*
    | Anti-sniping. A bid inside the window pushes the close out, so a late
    | bid cannot steal a lot from someone who would have bid again.
    */
    'anti_snipe' => [
        'window_seconds' => (int) env('AUCTION_SNIPE_WINDOW', 120),
        'extend_seconds' => (int) env('AUCTION_SNIPE_EXTEND', 120),
        'max_extensions' => (int) env('AUCTION_SNIPE_MAX', 20),
    ],

    /*
    | Bid sizing. The increment is the seller's, bounded by these.
    */
    'increment' => [
        'min_cents' => 100,
        'default_cents' => 1_000,
    ],

    /*
    | How long a seller may run a lot for.
    */
    'duration' => [
        'min_hours' => 1,
        'max_days' => 30,
        'default_days' => 7,
    ],

];
