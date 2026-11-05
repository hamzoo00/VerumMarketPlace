<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\ShipmentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ShipmentEvent> */
class ShipmentEventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->dispatched(),
            'source' => ShipmentEvent::SOURCE_COURIER,
            'status' => 'in_transit',
            'description' => 'Departed facility',
            'location' => fake()->city(),
            'occurred_at' => now(),
            'fingerprint' => Str::random(40),
        ];
    }

    /** Written by the seller rather than scanned. */
    public function fromSeller(): static
    {
        return $this->state(fn (): array => [
            'source' => ShipmentEvent::SOURCE_SELLER,
            'status' => 'note',
            'location' => null,
        ]);
    }
}
