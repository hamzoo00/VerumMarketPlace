<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketplace identity
    |--------------------------------------------------------------------------
    | Contact details and copy shared across the public site. Kept in config so
    | templates never hardcode an address or handle.
    */

    'tagline' => env('VERUM_TAGLINE', 'Natural & certified gemstones'),

    'signature' => env('VERUM_SIGNATURE', 'Exactly as stated.'),

    'contact' => [
        'email' => env('VERUM_CONTACT_EMAIL', 'hello@verumgems.com'),
        'phone' => env('VERUM_CONTACT_PHONE', '+92 300 000 0000'),
        'whatsapp' => env('VERUM_WHATSAPP', '10000000000'),
        'address' => env('VERUM_ADDRESS', 'Saddar Gem Market, Peshawar, Pakistan'),
    ],

    'social' => [
        'instagram' => env('VERUM_INSTAGRAM'),
        'youtube' => env('VERUM_YOUTUBE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    | While the backend is still being built, the storefront shows a banner and
    | disables actions that would otherwise take payment.
    */

    'demo_mode' => (bool) env('VERUM_DEMO_MODE', true),

];
