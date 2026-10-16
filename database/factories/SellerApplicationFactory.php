<?php

namespace Database\Factories;

use App\Models\SellerAccount;
use App\Models\SellerApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SellerApplication> */
class SellerApplicationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'seller_account_id' => SellerAccount::factory(),
            'reference' => SellerApplication::generateReference(),
            'status' => SellerApplication::STATUS_SUBMITTED,
            'business_name' => fake()->company(),
            'country' => fake()->country(),
            'trade_license_number' => fake()->numerify('TL-######'),
            'specialties' => 'Ruby, Sapphire',
            'about' => fake()->paragraph(),
            'submitted_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => SellerApplication::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => 'admin@verum.test',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => SellerApplication::STATUS_REJECTED,
            'reviewed_at' => now(),
            'decision_note' => 'Trade license could not be verified.',
        ]);
    }
}
