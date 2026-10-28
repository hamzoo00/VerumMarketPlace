<?php

/*
|--------------------------------------------------------------------------
| Platform rules
|--------------------------------------------------------------------------
|
| The *shape* of what an admin can switch: each key, its type, and what it
| falls back to before anyone has touched it. The live values live in the
| `platform_settings` table, because a commission rate is a business
| decision and must not need a deploy.
|
| Anything an engineer decides stays in a normal config file.
|
*/

return [

    'settings' => [

        /*
         * Commission.
         *
         * A percentage of the item subtotal, recorded on each order as
         * it is placed. Postage is excluded: the seller pays the carrier
         * out of it, so charging a cut of it would be charging them to
         * hand money on.
         */
        'commission_enabled' => ['type' => 'bool', 'default' => false],
        'commission_percent' => ['type' => 'float', 'default' => 0.0, 'min' => 0, 'max' => 50],

        /*
         * Listing approval.
         *
         * Deliberately independent of commission. A platform that takes
         * no cut may still want to see what goes up, and one that does
         * may trust its sellers — tying them together would force a
         * choice nobody asked for.
         */
        'listing_approval_required' => ['type' => 'bool', 'default' => false],

        // Featuring is premium-only and requested by the seller; this
        // only decides whether the queue is open at all.
        'featuring_enabled' => ['type' => 'bool', 'default' => true],

        // How many featured listings the storefront shows at once.
        'featured_slots' => ['type' => 'int', 'default' => 6, 'min' => 1, 'max' => 24],
    ],

    // Certificate references: VRC-2026-0042.
    'certificate_prefix' => 'VRC',

];
