<?php

namespace App\Actions\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Payments\PaymentFailed;

/**
 * Sends a buyer to PayPal to pay for an order.
 *
 * Every figure comes from the order row, which PlaceOrder recomputed
 * from the listing — nothing the browser sends decides what is charged.
 * The commission recorded on the order is what PayPal hands the
 * platform, so the two can never disagree.
 *
 * Each attempt is its own payment row: a buyer who abandons PayPal and
 * tries again leaves the first attempt on record rather than reusing it.
 */
class StartCheckout
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * @return string Where to send the buyer to approve the payment.
     *
     * @throws PaymentFailed
     */
    public function handle(Order $order): string
    {
        $order->loadMissing('seller');

        if ($order->status !== Order::STATUS_AWAITING_PAYMENT) {
            throw new PaymentFailed(__('This order is not waiting for payment.'));
        }

        if (! $order->seller?->canReceivePayments()) {
            throw new PaymentFailed(__('The seller has not finished setting up PayPal yet. We have let them know; please try again soon.'));
        }

        if ((int) $order->total_cents <= 0) {
            throw new PaymentFailed(__('There is nothing to pay on this order.'));
        }

        $payment = Payment::create([
            'purpose' => Payment::PURPOSE_ORDER,
            'order_id' => $order->id,
            'seller_id' => $order->seller_id,
            'buyer_id' => $order->buyer_id,
            'provider' => $this->gateway->name(),
            'status' => Payment::STATUS_CREATED,
            'amount_cents' => (int) $order->total_cents,
            // Never more than the total: PayPal refuses a fee above it.
            'fee_cents' => min((int) $order->commission_cents, (int) $order->total_cents),
            'currency' => $order->currency,
        ]);

        try {
            $redirect = $this->gateway->createCheckout(
                $payment,
                $order,
                route('buyer.orders.paid', $order),
                route('buyer.orders.pay.cancelled', $order),
            );
        } catch (PaymentFailed $e) {
            $payment->update(['status' => Payment::STATUS_FAILED, 'failure_reason' => $e->getMessage()]);

            throw $e;
        }

        $payment->update(['provider_order_id' => $redirect->id]);

        return $redirect->url;
    }
}
