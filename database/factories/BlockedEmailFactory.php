<?php

namespace Database\Factories;

use App\Models\BlockedEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BlockedEmail> */
class BlockedEmailFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'scope' => BlockedEmail::SCOPE_SELLER,
            'reason' => fake()->sentence(),
            'blocked_by' => 'admin',
        ];
    }
}
