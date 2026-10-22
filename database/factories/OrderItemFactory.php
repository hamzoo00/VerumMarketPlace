<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $price = fake()->numberBetween(10_000, 500_000);

        return [
            'order_id' => Order::factory(),
            'listing_id' => Listing::factory(),
            'title' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'unit_price_cents' => $price,
            'quantity' => 1,
            'line_total_cents' => $price,
        ];
    }
}
