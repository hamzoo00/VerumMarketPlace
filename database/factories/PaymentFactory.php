<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'purpose' => Payment::PURPOSE_ORDER,
            'order_id' => Order::factory(),
            'seller_id' => fn (array $attributes): ?int => Order::query()->find((int) $attributes['order_id'])?->seller_id,
            'buyer_id' => fn (array $attributes): ?int => Order::query()->find((int) $attributes['order_id'])?->buyer_id,
            'provider' => 'paypal',
            'provider_order_id' => Str::upper(Str::random(17)),
            'status' => Payment::STATUS_CREATED,
            'amount_cents' => fn (array $attributes): int => (int) Order::query()->find((int) $attributes['order_id'])?->total_cents,
            'fee_cents' => 0,
            'currency' => 'USD',
        ];
    }

    /** Captured: the money has reached the seller. */
    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => Payment::STATUS_COMPLETED,
            'provider_capture_id' => Str::upper(Str::random(17)),
            'completed_at' => now(),
        ]);
    }
}
