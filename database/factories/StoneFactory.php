<?php

namespace Database\Factories;

use App\Models\AuctionLot;
use App\Models\GemType;
use App\Models\Seller;
use App\Models\Stone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Stone> */
class StoneFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        // sentence() is typed as string, unlike words(), which PHPStan sees as array|string.
        $name = rtrim(fake()->unique()->sentence(3), '.');
        $untreated = fake()->boolean();

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'gem_type_id' => GemType::factory(),
            'seller_id' => Seller::factory(),
            'price_cents' => fake()->numberBetween(9_500, 150_000),
            'currency' => 'USD',
            'carat' => fake()->randomFloat(2, 0.5, 12),
            'dimensions' => '8.1 × 6.4 × 4.2 mm',
            'shape' => fake()->randomElement(['Oval', 'Cushion', 'Emerald cut', 'Pear', 'Cabochon']),
            'cut' => 'Oval, brilliant',
            'color' => fake()->safeColorName(),
            'transparency' => fake()->randomElement(['Transparent', 'Translucent']),
            'treatment' => $untreated ? 'No indication of heat' : 'Heated (H)',
            'untreated' => $untreated,
            'origin' => fake()->city(),
            'country' => fake()->country(),
            'report_number' => 'VR-'.fake()->year().'-'.fake()->unique()->numerify('######'),
            'report_date' => fake()->date(),
            'lab' => fake()->randomElement(['GIA', 'AGL']),
            'lab_number' => fake()->numerify('##########'),
            'description' => fake()->paragraph(),
            'comments' => fake()->sentence(),
            'position' => fake()->numberBetween(0, 50),
        ];
    }

    /** A stone offered at auction rather than a fixed price. */
    public function asLot(): static
    {
        return $this->state(fn (): array => ['price_cents' => null])
            ->afterCreating(function (Stone $stone): void {
                AuctionLot::factory()->for($stone)->create();
            });
    }
}
