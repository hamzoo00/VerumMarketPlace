<?php

namespace App\Jobs;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Payments\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Gives PayPal the parcel's tracking number once the seller posts it.
 *
 * PayPal's seller protection covers an "item not received" claim when
 * the payment carries tracking that shows delivery, so this is worth
 * doing every time. Queued: the seller marking an order dispatched must
 * not wait on PayPal, or fail because PayPal is slow.
 */
class PushTrackingToPayPal implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public int $orderId)
    {
        $this->afterCommit();
    }

    public function handle(PaymentGateway $gateway): void
    {
        $order = Order::find($this->orderId);

        if ($order === null || blank($order->tracking_number)) {
            return;
        }

        $payment = $order->payments()
            ->whereIn('status', [Payment::STATUS_COMPLETED, Payment::STATUS_PARTIALLY_REFUNDED])
            ->whereNull('tracking_sent_at')
            ->latest('id')
            ->first();

        if ($payment === null) {
            return;
        }

        try {
            $gateway->addTracking($payment, (string) $order->tracking_carrier, (string) $order->tracking_number);
        } catch (PaymentFailed $e) {
            Log::warning('Tracking could not be sent to PayPal.', ['order' => $order->reference, 'error' => $e->getMessage()]);

            // Let the queue retry with backoff.
            throw $e;
        }

        $payment->update(['tracking_sent_at' => now()]);
    }
}
