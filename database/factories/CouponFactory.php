<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'seller_id' => Seller::factory(),
            'code' => Str::upper(fake()->unique()->bothify('????##')),
            'type' => Coupon::TYPE_PERCENT,
            'percent' => 10,
            'per_buyer_limit' => 1,
            'used_count' => 0,
            'is_active' => true,
        ];
    }
}
