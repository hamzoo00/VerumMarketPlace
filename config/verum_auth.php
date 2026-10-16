<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email throttling
    |--------------------------------------------------------------------------
    |
    | Password reset links and password-change confirmations may be sent a few
    | times in quick succession, after which the sender must wait. Keeps a
    | mailbox from being flooded without blocking a genuine retry.
    |
    */

    'throttle' => [
        'reset' => [
            'attempts' => (int) env('AUTH_RESET_ATTEMPTS', 3),
            'decay_seconds' => (int) env('AUTH_RESET_DECAY', 30),
        ],
        'change' => [
            'attempts' => (int) env('AUTH_CHANGE_ATTEMPTS', 3),
            'decay_seconds' => (int) env('AUTH_CHANGE_DECAY', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password strength
    |--------------------------------------------------------------------------
    |
    | Mirrored client-side in resources/js/verum/validate.js. Change both.
    |
    */

    'password' => [
        'min' => (int) env('AUTH_PASSWORD_MIN', 10),
    ],

];
