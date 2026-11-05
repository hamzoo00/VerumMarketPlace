<?php

namespace App\Support\Payments;

/** Something PayPal created, and where to send the person to approve it. */
final readonly class GatewayRedirect
{
    public function __construct(
        public string $id,
        public string $url,
    ) {}
}
