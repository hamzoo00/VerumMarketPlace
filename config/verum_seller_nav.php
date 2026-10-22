<?php

/*
|--------------------------------------------------------------------------
| Seller navigation
|--------------------------------------------------------------------------
|
| The seller area's sidebar. `route` is a route name under the `seller.`
| prefix; `built` marks whether the destination is functional yet, so the
| sidebar can show the full shape without pretending everything works.
|
*/

return [

    'selling' => [
        'label' => 'Selling',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Selling dashboard', 'icon' => 'grid', 'built' => true],
            ['key' => 'listings', 'label' => 'Manage inventory', 'icon' => 'gem', 'built' => true],
            ['key' => 'listings.create', 'label' => 'Sell an item', 'icon' => 'plus', 'built' => true],
            ['key' => 'messages', 'label' => 'Store messages', 'icon' => 'chat', 'built' => true],
            ['key' => 'checkouts', 'label' => 'Pending checkouts', 'icon' => 'bag', 'built' => true],
            ['key' => 'orders', 'label' => 'Sales', 'icon' => 'box', 'built' => true],
            ['key' => 'shipping', 'label' => 'Shipping', 'icon' => 'globe', 'built' => true],
            ['key' => 'offers', 'label' => 'Offers', 'icon' => 'scale', 'built' => true],
        ],
    ],

    'growth' => [
        'label' => 'Growth',
        'items' => [
            ['key' => 'coupons', 'label' => 'Coupon campaigns', 'icon' => 'spark', 'built' => true],
            ['key' => 'events', 'label' => 'Live events', 'icon' => 'flame', 'built' => false],
            ['key' => 'templates', 'label' => 'Templates', 'icon' => 'file', 'built' => false],
            ['key' => 'feedback', 'label' => 'Store feedback', 'icon' => 'star', 'built' => false],
            ['key' => 'cases', 'label' => 'Disputes', 'icon' => 'shield', 'built' => true],
        ],
    ],

    'account' => [
        'label' => 'Account',
        'items' => [
            ['key' => 'storefront', 'label' => 'My store', 'icon' => 'shield', 'built' => true],
            ['key' => 'settings', 'label' => 'Settings', 'icon' => 'user', 'built' => true],
        ],
    ],

];
