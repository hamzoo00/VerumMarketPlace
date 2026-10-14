<?php

namespace Database\Factories;

use App\Models\AuctionLot;
use App\Models\Stone;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuctionLot> */
class AuctionLotFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $current = fake()->numberBetween(10_000, 100_000);

        return [
            'stone_id' => Stone::factory(),
            'lot_number' => fake()->unique()->numerify('0###'),
            'current_bid_cents' => $current,
            'bid_increment_cents' => 2_000,
            'estimate_low_cents' => (int) ($current * 1.3),
            'estimate_high_cents' => (int) ($current * 1.8),
            'bids_count' => fake()->numberBetween(0, 40),
            'ends_at' => now()->addHours(fake()->numberBetween(1, 72)),
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (): array => ['ends_at' => now()->subHour()]);
    }

    public function closingSoon(): static
    {
        return $this->state(fn (): array => ['ends_at' => now()->addMinutes(5)]);
    }
}
