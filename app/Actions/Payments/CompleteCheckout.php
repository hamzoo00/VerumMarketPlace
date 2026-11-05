<?php

namespace App\Actions\Payments;

use App\Actions\Orders\AdvanceOrder;
use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentUpdate;
use App\Support\Payments\CapturedPayment;
use App\Support\Payments\PaymentFailed;
use App\Support\StaffAlerts;
use Illuminate\Support\Facades\DB;

/**
 * Takes the money for an approved checkout and marks the order paid.
 *
 * Reached two ways — the buyer coming back from PayPal, and PayPal's
 * PAYMENT.CAPTURE.COMPLETED webhook for a buyer who closed the tab — so
 * it is idempotent: the payment row is locked, and one already settled
 * is returned as it is. The order is paid only here, never by a button,
 * so no sale counts as paid until PayPal says the money moved.
 */
class CompleteCheckout
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly AdvanceOrder $advance,
    ) {}

    /** @throws PaymentFailed */
    public function handle(string $providerOrderId): Payment
    {
        $payment = Payment::query()
            ->where('purpose', Payment::PURPOSE_ORDER)
            ->where('provider_order_id', $providerOrderId)
            ->latest('id')
            ->first() ?? throw new PaymentFailed(__('We could not find that payment. Please start again.'));

        if ($payment->isSettled()) {
            return $payment;
        }

        try {
            $captured = $this->gateway->capture($providerOrderId);
        } catch (PaymentFailed $e) {
            $payment->update(['status' => Payment::STATUS_FAILED, 'failure_reason' => $e->getMessage()]);

            throw $e;
        }

        return $this->record($payment, $captured);
    }

    private function record(Payment $payment, CapturedPayment $captured): Payment
    {
        return DB::transaction(function () use ($payment, $captured): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->isSettled()) {
                return $payment;
            }

            if (! $captured->isCompleted()) {
                $payment->update([
                    'status' => $captured->isPending() ? Payment::STATUS_PENDING : Payment::STATUS_FAILED,
                    'provider_capture_id' => $captured->captureId,
                    'failure_reason' => $captured->isPending() ? null : __('PayPal did not complete the payment.'),
                ]);

                return $payment;
            }

            $payment->update([
                'status' => Payment::STATUS_COMPLETED,
                'provider_capture_id' => $captured->captureId,
                // What PayPal confirmed, which is what was actually charged.
                'amount_cents' => $captured->amountCents ?: $payment->amount_cents,
                'fee_cents' => $captured->amountCents > 0 ? $captured->feeCents : $payment->fee_cents,
                'completed_at' => now(),
                'failure_reason' => null,
            ]);

            $this->settleOrder($payment, $captured);

            return $payment;
        });
    }

    private function settleOrder(Payment $payment, CapturedPayment $captured): void
    {
        $order = Order::query()->lockForUpdate()->find($payment->order_id);

        if ($order === null) {
            return;
        }

        if ($order->status !== Order::STATUS_AWAITING_PAYMENT) {
            // Paid for something already cancelled — the buyer is owed
            // their money back, and only a person can sort that out.
            StaffAlerts::paymentProblem(
                $payment,
                __('Payment for a closed order'),
                __(':reference was paid while it was :status. The buyer needs a refund.', [
                    'reference' => $order->reference,
                    'status' => mb_strtolower($order->statusLabel()),
                ]),
            );

            return;
        }

        // An auction win or an accepted offer had no address: use the one
        // the buyer chose in PayPal, which is where PayPal protects delivery.
        if (! $order->hasAddress() && $captured->address !== null) {
            $order->update([
                'ship_to_name' => $captured->address['name'],
                'ship_to_line1' => $captured->address['line1'],
                'ship_to_line2' => $captured->address['line2'],
                'ship_to_city' => $captured->address['city'],
                'ship_to_region' => $captured->address['region'],
                'ship_to_postcode' => $captured->address['postcode'],
                'ship_to_country' => $captured->address['country'],
            ]);
        }

        $paid = $this->advance->markPaid($order);

        $paid->buyer?->notify(new PaymentUpdate(
            'paid',
            __('Payment received for :reference', ['reference' => $paid->reference]),
            __('Thank you — :amount reached the seller through PayPal. They will post your stone and add tracking here.', [
                'amount' => $payment->amount()->format(),
            ]),
            route('buyer.orders.show', $paid),
        ));

        $paid->seller?->account?->notify(new PaymentUpdate(
            'received',
            __(':reference is paid — ready to post', ['reference' => $paid->reference]),
            __('The buyer paid :amount into your PayPal account. Post the stone and add the tracking number to the order.', [
                'amount' => $payment->amount()->format(),
            ]),
            route('seller.orders.show', $paid),
            'box',
        ));
    }
}
