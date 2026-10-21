<?php

/*
|--------------------------------------------------------------------------
| URL segments
|--------------------------------------------------------------------------
|
| Every public path segment in one place. Change a value here and the route,
| every link built with route(), and every redirect follow automatically —
| there is no path string hardcoded anywhere else.
|
| Route *names* never change, so renaming a URL never breaks a view.
|
| Example: set 'shop' => 'gemstones' and /shop becomes /gemstones everywhere.
|
*/

return [

    /*
    | Public storefront
    */
    'shop' => env('ROUTE_SHOP', 'shop'),
    'stones' => env('ROUTE_STONES', 'stones'),
    'listings' => env('ROUTE_LISTINGS_PUBLIC', 'item'),
    'auctions' => env('ROUTE_AUCTIONS', 'auctions'),
    'sellers' => env('ROUTE_SELLERS', 'sellers'),
    'verify' => env('ROUTE_VERIFY', 'verify'),
    'about' => env('ROUTE_ABOUT', 'about'),
    'contact' => env('ROUTE_CONTACT', 'contact'),
    'sell' => env('ROUTE_SELL', 'sell'),
    'help' => env('ROUTE_HELP', 'help'),
    'cart' => env('ROUTE_CART', 'cart'),
    'checkout' => env('ROUTE_CHECKOUT', 'checkout'),
    'search' => env('ROUTE_SEARCH', 'search'),

    /*
    | Account areas
    */
    'buyer' => env('ROUTE_BUYER', 'buyer'),
    'seller' => env('ROUTE_SELLER', 'seller'),

    /*
    | Admin
    |
    | Worth changing in production: a non-obvious segment keeps the sign-in
    | page out of casual reach. It is obscurity, not security — the guard is
    | what actually protects the area.
    */
    'admin' => env('ROUTE_ADMIN', 'admin'),

    /*
    | Shared sub-paths, reused across the account areas.
    */
    'auth' => [
        'login' => env('ROUTE_LOGIN', 'login'),
        'logout' => env('ROUTE_LOGOUT', 'logout'),
        'register' => env('ROUTE_REGISTER', 'register'),
        'apply' => env('ROUTE_APPLY', 'apply'),
        'forgot_password' => env('ROUTE_FORGOT_PASSWORD', 'forgot-password'),
        'reset_password' => env('ROUTE_RESET_PASSWORD', 'reset-password'),
        'password' => env('ROUTE_PASSWORD', 'password'),
    ],

    'account' => [
        'dashboard' => env('ROUTE_DASHBOARD', 'dashboard'),
        'settings' => env('ROUTE_SETTINGS', 'settings'),
        'orders' => env('ROUTE_ORDERS', 'orders'),
        'saved' => env('ROUTE_SAVED', 'saved'),
        'bids' => env('ROUTE_BIDS', 'bids'),
        'listings' => env('ROUTE_LISTINGS', 'listings'),
        'storefront' => env('ROUTE_STOREFRONT', 'storefront'),
        'status' => env('ROUTE_STATUS', 'status'),
        'applications' => env('ROUTE_APPLICATIONS', 'applications'),
        'shipping' => env('ROUTE_SHIPPING', 'shipping'),
        'offers' => env('ROUTE_OFFERS', 'offers'),
        'coupons' => env('ROUTE_COUPONS', 'coupons'),
        'cases' => env('ROUTE_CASES', 'cases'),
        'messages' => env('ROUTE_MESSAGES', 'messages'),
        'moderation' => env('ROUTE_MODERATION', 'moderation'),
        'reviews' => env('ROUTE_REVIEWS', 'reviews'),
        'bids_place' => env('ROUTE_BID', 'bid'),
    ],

];
