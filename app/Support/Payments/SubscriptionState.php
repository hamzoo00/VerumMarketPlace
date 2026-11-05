<?php

namespace App\Support\Payments;

use Carbon\CarbonImmutable;

/** A premium subscription as PayPal currently sees it. */
final readonly class SubscriptionState
{
    public const ACTIVE = 'active';

    public function __construct(
        public string $id,
        /** PayPal's status, lower-cased: active, approval_pending, cancelled… */
        public string $status,
        public ?CarbonImmutable $nextBillingAt,
    ) {}

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }
}
