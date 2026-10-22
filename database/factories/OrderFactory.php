<?php

namespace Database\Factories;

use App\Models\Buyer;
use App\Models\Order;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(10_000, 500_000);
        $shipping = fake()->numberBetween(0, 4_000);

        return [
            'reference' => Order::newReference(),
            'buyer_id' => Buyer::factory(),
            'seller_id' => Seller::factory(),
            'origin' => Order::ORIGIN_CHECKOUT,
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'currency' => 'USD',
            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shipping,
            'discount_cents' => 0,
            'total_cents' => $subtotal + $shipping,
            'handling_days' => 2,
            'ship_to_name' => fake()->name(),
            'ship_to_line1' => fake()->streetAddress(),
            'ship_to_city' => fake()->city(),
            'ship_to_postcode' => fake()->postcode(),
            'ship_to_country' => 'US',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function dispatched(): static
    {
        return $this->state(fn (): array => [
            'status' => Order::STATUS_DISPATCHED,
            'paid_at' => now()->subDay(),
            'dispatched_at' => now(),
            'tracking_carrier' => 'DHL',
            'tracking_number' => (string) fake()->numberBetween(100000000, 999999999),
        ]);
    }
}
