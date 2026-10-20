<?php

namespace Database\Factories;

use App\Models\Bid;
use App\Models\Buyer;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Bid> */
class BidFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory()->auction(),
            'buyer_id' => Buyer::factory(),
            'amount_cents' => fake()->numberBetween(25_000, 500_000),
            'currency' => 'USD',
            'extended_auction' => false,
        ];
    }

    public function outbid(): static
    {
        return $this->state(fn (): array => ['outbid_at' => now()]);
    }
}
