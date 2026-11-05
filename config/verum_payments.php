<?php

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
|
| Every payment runs through PayPal: a buyer pays the seller's own PayPal
| account and PayPal hands the platform its commission on the way
| (PayPal's "Multiparty" platform fees), so the platform never holds a
| buyer's money and disputes stay inside PayPal. Premium is a monthly
| PayPal subscription paid to the platform.
|
| `driver` decides who answers:
|
|   fake    a simulator inside this app — checkout, connecting PayPal and
|           subscribing open a local page with Approve / Cancel / Decline.
|           Nothing leaves the machine. Never available in production.
|   paypal  the real PayPal REST API, in the mode set by PAYPAL_MODE
|           (`sandbox` while testing, `live` in production).
|
| Credentials live in config/services.php ('paypal'), not here.
|
*/

return [

    'driver' => env('PAYMENTS_DRIVER', 'fake'),

    // Charged and settled in USD, like every price on the site.
    'currency' => 'USD',

    'hosts' => [
        'sandbox' => 'https://api-m.sandbox.paypal.com',
        'live' => 'https://api-m.paypal.com',
    ],

    'premium' => [
        // What the monthly plan costs. Changing it means a new PayPal
        // plan (`./verum artisan paypal:premium-plan`); subscribers keep
        // the price they signed up at, which is how PayPal plans work.
        'price_cents' => (int) env('PREMIUM_PRICE_CENTS', 2900),
        'name' => 'Premium seller',

        // Premium runs to the next billing date plus this, so a renewal
        // PayPal collects a little late does not switch it off and on.
        'grace_days' => 3,
    ],

    /*
    | Countries whose residents cannot open a PayPal account, so a seller
    | there could never be paid. Shown as a warning on seller
    | applications; admins decline them. A starting list — check PayPal's
    | own country list before relying on it.
    */
    'unavailable_countries' => ['AF', 'BD', 'CU', 'IR', 'KP', 'PK', 'SY'],

];
