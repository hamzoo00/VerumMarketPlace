<?php

namespace App\Actions\Payments;

use App\Actions\Orders\AdvanceOrder;
use App\Contracts\PaymentGateway;
use App\Models\Money;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentUpdate;
use App\Support\Payments\PaymentFailed;
use App\Support\Payments\RefundedPayment;
use Illuminate\Support\Facades\DB;

/**
 * Money going back to a buyer.
 *
 * Two ways in: the seller cancels a paid order here (refunded through
 * PayPal, then cancelled), or the seller refunds inside PayPal and the
 * webhook reports it. Both end in record(), which counts each PayPal
 * refund id once — our own refund call is followed by a webhook about
 * the very same refund.
 */
class SettleRefund
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly AdvanceOrder $advance,
    ) {}

    /**
     * Cancel a paid order and give the buyer their money back.
     *
     * Refunded first: if PayPal refuses, the order stays paid rather
     * than reading cancelled while the buyer is still out of pocket.
     *
     * @throws PaymentFailed
     */
    public function cancelPaidOrder(Order $order, ?string $reason = null, bool $notify = true): Order
    {
        $payment = $this->paymentFor($order);

        if ($payment === null) {
            return $this->advance->cancel($order, $reason);
        }

        $refund = $this->gateway->refund($payment);

        return DB::transaction(function () use ($order, $payment, $refund, $reason, $notify): Order {
            $this->record($payment, $refund, notify: false);

            $cancelled = $this->advance->cancel($order, $reason);

            if (! $notify) {
                return $cancelled;
            }

            $cancelled->buyer?->notify(new PaymentUpdate(
                'refunded',
                __(':reference was cancelled and refunded', ['reference' => $cancelled->reference]),
                __('The seller cancelled this order and :amount is on its way back to you through PayPal.', [
                    'amount' => $payment->amount()->format(),
                ]),
                route('buyer.orders.show', $cancelled),
                'return',
            ));

            return $cancelled;
        });
    }

    /**
     * Count one refund against its payment.
     *
     * A full refund of an order not yet posted cancels it, so the stone
     * goes back on sale; one already on its way is a return, and the
     * order keeps its status.
     *
     * @return bool False when this refund was already counted.
     */
    public function record(Payment $payment, RefundedPayment $refund, bool $notify = true): bool
    {
        return DB::transaction(function () use ($payment, $refund, $notify): bool {
            $first = DB::table('payment_events')->insertOrIgnore([
                'provider' => $payment->provider,
                'event_id' => 'refund:'.$refund->id,
                'type' => 'REFUND',
                'resource_id' => $payment->provider_capture_id,
                'created_at' => now(),
            ]);

            if ($first === 0) {
                return false;
            }

            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $total = min((int) $payment->amount_cents, (int) $payment->refunded_cents + $refund->amountCents);
            $full = $total >= (int) $payment->amount_cents;

            $payment->update([
                'refunded_cents' => $total,
                'status' => $full ? Payment::STATUS_REFUNDED : Payment::STATUS_PARTIALLY_REFUNDED,
            ]);

            if ($notify) {
                $this->closeOrderAfter($payment, $full, $refund->amountCents);
            }

            return true;
        });
    }

    private function closeOrderAfter(Payment $payment, bool $full, int $refundedCents): void
    {
        $order = $payment->order;

        if ($order === null) {
            return;
        }

        if ($full && $order->status === Order::STATUS_PAID) {
            $order = $this->advance->cancel($order, __('Refunded through PayPal.'));
        }

        $order->buyer?->notify(new PaymentUpdate(
            'refunded',
            __('Refund on :reference', ['reference' => $order->reference]),
            __(':amount of your payment is on its way back to you through PayPal.', [
                'amount' => Money::fromCents($refundedCents, $payment->currency)->format(),
            ]),
            route('buyer.orders.show', $order),
            'return',
        ));
    }

    private function paymentFor(Order $order): ?Payment
    {
        return $order->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->latest('id')
            ->first();
    }
}
