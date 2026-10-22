<?php

namespace Database\Factories;

use App\Models\Seller;
use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShippingRate> */
class ShippingRateFactory extends Factory
{
    protected $model = ShippingRate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'seller_id' => Seller::factory(),
            'service' => 'standard',
            'destination' => 'domestic',
            'price_cents' => fake()->numberBetween(500, 5_000),
            'transit_min_days' => 3,
            'transit_max_days' => 7,
            'tracked' => true,
            'insured' => false,
            'is_active' => true,
        ];
    }
}
