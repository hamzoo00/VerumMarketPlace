<?php

namespace Database\Factories;

use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Seller> */
class SellerFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'initials' => Str::upper(Str::substr($name, 0, 2)),
            'location' => fake()->city().', '.fake()->country(),
            'about' => fake()->paragraph(),
            'ships' => 'Ships in '.fake()->numberBetween(2, 5).' business days',
            'listings_count' => fake()->numberBetween(10, 200),
            'sold_label' => (string) fake()->numberBetween(50, 2000),
            'rating' => fake()->randomFloat(1, 4, 5),
            'reviews_count' => fake()->numberBetween(5, 400),
            'years_active' => fake()->numberBetween(1, 20),
            'since' => (string) fake()->numberBetween(2015, 2024),
            'verified_on' => fake()->date(),
            'accent' => fake()->randomElement(['ruby', 'sapphire', 'emerald', 'amethyst', 'topaz']),
            'specialties' => fake()->randomElements(['Ruby', 'Sapphire', 'Emerald', 'Topaz', 'Spinel'], 2),
        ];
    }
}
