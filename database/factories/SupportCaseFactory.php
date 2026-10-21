<?php

namespace Database\Factories;

use App\Models\Buyer;
use App\Models\SupportCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupportCase> */
class SupportCaseFactory extends Factory
{
    protected $model = SupportCase::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'reference' => SupportCase::newReference(SupportCase::KIND_DISPUTE),
            'kind' => SupportCase::KIND_DISPUTE,
            'buyer_id' => Buyer::factory(),
            'reason' => 'not_as_described',
            'body' => fake()->paragraph(),
            'status' => SupportCase::STATUS_OPEN,
        ];
    }

    public function audit(): static
    {
        return $this->state(fn (): array => [
            'kind' => SupportCase::KIND_AUDIT,
            'reference' => SupportCase::newReference(SupportCase::KIND_AUDIT),
            'reason' => 'misdescribed',
            'seller_id' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => SupportCase::STATUS_RESOLVED,
            'resolution' => 'dismissed',
            'resolved_at' => now(),
        ]);
    }
}
