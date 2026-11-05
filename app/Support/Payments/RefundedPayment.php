<?php

namespace App\Support\Payments;

/**
 * Money PayPal gave back. The id is what stops one refund being counted
 * twice — once from our own call, again from PayPal's webhook about it.
 */
final readonly class RefundedPayment
{
    public function __construct(
        public string $id,
        public int $amountCents,
    ) {}
}
