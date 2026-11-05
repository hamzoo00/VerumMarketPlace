<?php

namespace App\Support\Payments;

use RuntimeException;

/**
 * A payment step did not go through.
 *
 * The message is written for the person who clicked — PayPal's own
 * error, with its debug id, goes to the log instead.
 */
class PaymentFailed extends RuntimeException {}
