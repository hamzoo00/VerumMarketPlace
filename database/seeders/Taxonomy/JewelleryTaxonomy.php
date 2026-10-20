<?php

namespace Database\Seeders\Taxonomy;

/**
 * Finished jewellery categories.
 *
 * Shape matches StoneTaxonomy: 'Parent' => ['Child', ...].
 */
final class JewelleryTaxonomy
{
    /** @return array<string, list<string>> */
    public static function all(): array
    {
        return [
            'Rings' => [
                'Engagement Rings',
                'Wedding Bands',
                'Cocktail Rings',
                'Eternity Rings',
                'Signet Rings',
                'Mens Rings',
                'Stacking Rings',
            ],
            'Earrings' => [
                'Stud Earrings',
                'Drop Earrings',
                'Hoop Earrings',
                'Chandelier Earrings',
                'Huggie Earrings',
                'Ear Climbers',
            ],
            'Necklaces & Pendants' => [
                'Pendants',
                'Chains',
                'Chokers',
                'Lockets',
                'Statement Necklaces',
                'Beaded Necklaces',
            ],
            'Bracelets' => [
                'Tennis Bracelets',
                'Bangles',
                'Charm Bracelets',
                'Cuffs',
                'Beaded Bracelets',
            ],
            'Brooches & Pins' => [
                'Brooches',
                'Lapel Pins',
                'Tie Pins',
            ],
            'Sets' => [
                'Bridal Sets',
                'Necklace & Earring Sets',
                'Full Parures',
            ],
            'Mens Jewellery' => [
                'Cufflinks',
                'Mens Bracelets',
                'Mens Chains',
                'Tie Clips',
            ],
            'Antique & Estate' => [
                'Art Deco',
                'Edwardian',
                'Georgian',
                'Mid Century',
                'Victorian',
            ],
            'Loose Settings & Findings' => [
                'Ring Mounts',
                'Pendant Mounts',
                'Clasps',
                'Earring Findings',
            ],
            'Bespoke & Custom' => [
                'Custom Commissions',
                'Remodelling',
            ],
        ];
    }
}
