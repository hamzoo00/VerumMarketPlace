<?php

/*
|--------------------------------------------------------------------------
| Seller categories
|--------------------------------------------------------------------------
|
| What a seller primarily trades in, and the specialties available under each.
| Drives the application form's category dropdown and its dependent
| checkboxes, so adding a line of business is a config change.
|
*/

return [

    'categories' => [
        'stones' => [
            'label' => 'Loose stones',
            'specialties' => [
                'ruby' => 'Ruby',
                'sapphire' => 'Sapphire',
                'emerald' => 'Emerald',
                'amethyst' => 'Amethyst',
                'topaz' => 'Topaz',
                'spinel' => 'Spinel',
                'garnet' => 'Garnet',
                'tourmaline' => 'Tourmaline',
                'star-stones' => 'Star stones',
                'rough' => 'Rough / uncut',
            ],
        ],

        'jewellery' => [
            'label' => 'Jewellery',
            'specialties' => [
                'rings' => 'Rings',
                'earrings' => 'Earrings',
                'necklaces' => 'Necklaces & pendants',
                'bracelets' => 'Bracelets',
                'bespoke' => 'Bespoke commissions',
                'antique' => 'Antique & estate',
            ],
        ],

        'both' => [
            'label' => 'Both stones and jewellery',
            // Resolved at runtime from the two sets above.
            'specialties' => [],
        ],
    ],

];
