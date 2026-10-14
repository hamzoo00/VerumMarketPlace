<?php

namespace Database\Factories;

use App\Models\GemType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<GemType> */
class GemTypeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $label = fake()->unique()->randomElement(['Ruby', 'Sapphire', 'Emerald', 'Amethyst', 'Topaz', 'Spinel', 'Garnet']);

        return [
            'slug' => Str::slug($label),
            'label' => $label,
            'plural' => Str::plural($label),
            'mineral' => fake()->randomElement(['Corundum', 'Beryl', 'Quartz', 'Topaz']),
            'mohs' => (string) fake()->randomFloat(1, 7, 9),
            'accent_color' => fake()->hexColor(),
            'note' => fake()->sentence(),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
