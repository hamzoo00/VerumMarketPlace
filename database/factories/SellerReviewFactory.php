<?php

namespace Database\Factories;

use App\Models\Seller;
use App\Models\SellerReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SellerReview> */
class SellerReviewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'seller_id' => Seller::factory(),
            'reviewer_name' => fake()->name(),
            'place' => fake()->city().', '.fake()->country(),
            'stars' => fake()->numberBetween(3, 5),
            'reviewed_on' => fake()->date(),
            'stone_name' => rtrim(fake()->sentence(2), '.'),
            'body' => fake()->paragraph(),
        ];
    }
}
