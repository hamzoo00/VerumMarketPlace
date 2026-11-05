<?php

namespace App\Support\Payments;

/**
 * What PayPal says happened when the money was taken.
 *
 * `address` is the delivery address the buyer chose in PayPal, used when
 * the order had none (an auction win or an accepted offer).
 */
final readonly class CapturedPayment
{
    public const COMPLETED = 'completed';

    /** Accepted, but PayPal is still checking — the money is not there yet. */
    public const PENDING = 'pending';

    public const FAILED = 'failed';

    /**
     * @param  array{name: ?string, line1: ?string, line2: ?string, city: ?string, region: ?string, postcode: ?string, country: ?string}|null  $address
     */
    public function __construct(
        public string $status,
        public ?string $captureId,
        public int $amountCents,
        public int $feeCents,
        public ?array $address = null,
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
