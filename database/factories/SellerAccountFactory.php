<?php

namespace Database\Factories;

use App\Models\SellerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<SellerAccount> */
class SellerAccountFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'country' => fake()->country(),
            // Unapproved by default: approval is the exception, not the rule.
            'approved_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['approved_at' => now()]);
    }
}
