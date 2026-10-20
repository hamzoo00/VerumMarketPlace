<?php

namespace Database\Seeders\Taxonomy;

/**
 * Gemstone categories, A–L.
 *
 * Shape: 'Parent' => ['Child', 'Child', ...]. A parent with an empty array is
 * a leaf. Split across two classes purely to keep each file readable.
 */
final class StoneTaxonomy
{
    /** @return array<string, list<string>> */
    public static function first(): array
    {
        return [
            'Agate' => ['Blue Lace Agate', 'Botswana Agate', 'Crazy Lace Agate', 'Dendritic Agate', 'Fire Agate', 'Moss Agate', 'Plume Agate'],
            'Alexandrite' => [],
            'Amazonite' => [],
            'Amber' => ['Baltic Amber', 'Burmite Amber', 'Dominican Amber'],
            'Amethyst' => ['Ametrine', 'Brandberg Amethyst', 'Chevron Amethyst', 'Rose de France', 'Vera Cruz Amethyst'],
            'Ammolite' => [],
            'Ammonite' => [],
            'Andalusite' => [],
            'Apatite' => ['Blue Apatite', 'Neon Apatite'],
            'Aquamarine' => ['Santa Maria Aquamarine', 'Espirito Santo Aquamarine'],
            'Aventurine' => [],
            'Azurite' => ['Azurite with Malachite'],
            'Benitoite' => [],
            'Beryl' => ['Bixbite', 'Goshenite', 'Golden Beryl', 'Heliodor', 'Morganite'],
            'Bloodstone' => [],
            'Carnelian' => [],
            'Charoite' => [],
            'Chrysoberyl' => ['Cats Eye Chrysoberyl'],
            'Chrysocolla' => [],
            'Chrysoprase' => [],
            'Citrine' => ['Madeira Citrine'],
            'Coral' => ['Black Coral', 'Red Coral'],
            'Danburite' => [],
            'Diamond' => ['Black Diamond', 'Rough Diamond', 'Salt and Pepper Diamond', 'White Diamond'],
            'Diopside' => ['Chrome Diopside'],
            'Dioptase' => [],
            'Emerald' => ['Colombian Emerald', 'Ethiopian Emerald', 'Swat Emerald', 'Trapiche Emerald', 'Zambian Emerald'],
            'Fluorite' => ['Rainbow Fluorite'],
            'Fossil' => ['Fossil Coral', 'Petrified Wood'],
            'Garnet' => ['Almandine Garnet', 'Demantoid Garnet', 'Grossular Garnet', 'Hessonite Garnet', 'Mali Garnet', 'Rhodolite Garnet', 'Spessartite Garnet', 'Tsavorite Garnet'],
            'Hematite' => [],
            'Howlite' => [],
            'Iolite' => [],
            'Jade' => ['Jadeite', 'Nephrite'],
            'Jasper' => ['Bumblebee Jasper', 'Ocean Jasper', 'Picture Jasper', 'Red Jasper'],
            'Kunzite' => [],
            'Kyanite' => ['Blue Kyanite'],
            'Labradorite' => ['Spectrolite'],
            'Lapis Lazuli' => [],
            'Larimar' => [],
            'Lepidolite' => [],
        ];
    }

    /** @return array<string, list<string>> */
    public static function second(): array
    {
        return [
            'Malachite' => [],
            'Moonstone' => ['Blue Moonstone', 'Rainbow Moonstone'],
            'Morganite' => [],
            'Obsidian' => ['Rainbow Obsidian', 'Snowflake Obsidian'],
            'Onyx' => [],
            'Opal' => ['Black Opal', 'Boulder Opal', 'Crystal Opal', 'Ethiopian Opal', 'Fire Opal', 'Matrix Opal', 'White Opal'],
            'Pearl' => ['Akoya Pearl', 'Freshwater Pearl', 'South Sea Pearl', 'Tahitian Pearl'],
            'Peridot' => [],
            'Prehnite' => [],
            'Pyrite' => [],
            'Quartz' => ['Clear Quartz', 'Lemon Quartz', 'Rose Quartz', 'Rutilated Quartz', 'Smoky Quartz', 'Strawberry Quartz', 'Tourmalinated Quartz'],
            'Rhodochrosite' => [],
            'Rhodonite' => [],
            'Ruby' => ['Burmese Ruby', 'Mozambique Ruby', 'Star Ruby', 'Unheated Ruby'],
            'Sapphire' => ['Bi Colour Sapphire', 'Ceylon Sapphire', 'Montana Sapphire', 'Padparadscha Sapphire', 'Pink Sapphire', 'Star Sapphire', 'Teal Sapphire', 'Yellow Sapphire'],
            'Scapolite' => [],
            'Selenite' => [],
            'Seraphinite' => [],
            'Serpentine' => [],
            'Shattuckite' => [],
            'Sodalite' => [],
            'Spinel' => ['Burmese Spinel', 'Cobalt Spinel', 'Mahenge Spinel', 'Red Spinel'],
            'Spodumene' => ['Blue Spodumene', 'Green Spodumene'],
            'Stichtite' => [],
            'Sugilite' => [],
            'Sunstone' => ['Oregon Sunstone'],
            'Super Seven' => [],
            'Tanzanite' => [],
            'Tektite' => ['Moldavite'],
            'Tiffany Stone' => [],
            'Tiger Eye' => ['Blue Tiger Eye', 'Red Tiger Eye'],
            'Topaz' => ['Blue Topaz', 'Imperial Topaz', 'Mystic Topaz', 'White Topaz'],
            'Tourmaline' => ['Bi Colour Tourmaline', 'Black Tourmaline', 'Chrome Tourmaline', 'Indicolite Tourmaline', 'Paraiba Tourmaline', 'Rubellite Tourmaline', 'Watermelon Tourmaline'],
            'Triphane' => [],
            'Turquoise' => ['Persian Turquoise', 'Sleeping Beauty Turquoise'],
            'Unakite' => [],
            'Variscite' => [],
            'Zebra Stone' => [],
            'Zircon' => ['Blue Zircon', 'Green Zircon', 'Pink Zircon', 'Red Zircon', 'White Zircon', 'Yellow Zircon'],
            'Zoisite' => ['Ruby in Zoisite'],
        ];
    }

    /**
     * Groupings that are not a single mineral.
     *
     * @return array<string, list<string>>
     */
    public static function collections(): array
    {
        return [
            'Beads' => ['Bead Strands', 'Carved Beads', 'Faceted Beads'],
            'Carvings' => ['Animal Carvings', 'Cameo', 'Intaglio'],
            'Gemstone Cuts' => ['Cabochon', 'Carved', 'Faceted', 'Rose Cut', 'Step Cut'],
            'Gemstone Lots' => ['Mixed Lots', 'Parcels', 'Wholesale Lots'],
            'Meteorite' => ['Pallasite', 'Tektite Meteorite'],
            'Mineral Specimens' => ['Cluster Specimens', 'Crystal Points', 'Geodes'],
            'Rough Gemstones' => ['Rough Corundum', 'Rough Emerald', 'Rough Opal', 'Rough Quartz'],
        ];
    }
}
