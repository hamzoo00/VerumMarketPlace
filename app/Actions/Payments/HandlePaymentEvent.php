<?php

namespace App\Actions\Payments;

use App\Models\Payment;
use App\Notifications\PaymentUpdate;
use App\Support\Payments\PaymentFailed;
use App\Support\Payments\RefundedPayment;
use App\Support\StaffAlerts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * What a verified PayPal webhook means for the marketplace.
 *
 * PayPal delivers an event until it is acknowledged, so the same one can
 * arrive several times. Its id is recorded in the same transaction that
 * acts on it: a repeat finds the id and does nothing, and a failure rolls
 * the id back so PayPal's retry gets another go.
 *
 * Disputes and chargebacks are PayPal's to settle; here they are only
 * recorded and the seller and admins told.
 */
class HandlePaymentEvent
{
    public function __construct(
        private readonly CompleteCheckout $checkout,
        private readonly SettleRefund $refunds,
        private readonly ManagePremium $premium,
    ) {}

    /** @param  array<string, mixed>  $event */
    public function handle(array $event, string $provider): void
    {
        $id = $event['id'] ?? null;
        $type = $event['event_type'] ?? null;
        $resource = is_array($event['resource'] ?? null) ? $event['resource'] : [];

        if (! is_string($id) || ! is_string($type)) {
            return;
        }

        DB::transaction(function () use ($id, $type, $resource, $provider): void {
            $first = DB::table('payment_events')->insertOrIgnore([
                'provider' => $provider,
                'event_id' => Str::limit($id, 80, ''),
                'type' => Str::limit($type, 80, ''),
                'resource_id' => is_string($resource['id'] ?? null) ? Str::limit($resource['id'], 80, '') : null,
                'created_at' => now(),
            ]);

            if ($first > 0) {
                $this->apply($type, $resource);
            }
        });
    }

    /** @param  array<string, mixed>  $resource */
    private function apply(string $type, array $resource): void
    {
        switch ($type) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->captureCompleted($resource);
                break;
            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.DECLINED':
                $this->captureFailed($resource);
                break;
            case 'PAYMENT.CAPTURE.REFUNDED':
                $this->refunded($resource);
                break;
            case 'PAYMENT.CAPTURE.REVERSED':
                $this->reversed($resource);
                break;
            case 'CUSTOMER.DISPUTE.CREATED':
                $this->disputed($resource);
                break;
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                $this->premium->activated($this->text($resource, 'id'));
                break;
            case 'BILLING.SUBSCRIPTION.CANCELLED':
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
            case 'BILLING.SUBSCRIPTION.EXPIRED':
                $this->premium->ended($this->text($resource, 'id'), Str::lower(Str::afterLast($type, '.')));
                break;
            case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
                $this->premium->paymentFailed($this->text($resource, 'id'));
                break;
            case 'PAYMENT.SALE.COMPLETED':
                $this->premium->renewed(
                    $this->text($resource, 'billing_agreement_id'),
                    $this->text($resource, 'id'),
                    $this->cents(data_get($resource, 'amount.total')),
                    $this->text($resource, 'amount.currency') ?: (string) config('verum_payments.currency'),
                );
                break;
        }
    }

    /**
     * The buyer paid and closed the tab before coming back — or the
     * capture was pending and has now cleared.
     *
     * @param  array<string, mixed>  $resource
     */
    private function captureCompleted(array $resource): void
    {
        $orderId = $this->text($resource, 'supplementary_data.related_ids.order_id');

        if ($orderId === '') {
            return;
        }

        try {
            $this->checkout->handle($orderId);
        } catch (PaymentFailed $e) {
            // Not ours, or PayPal refused: retrying the webhook will not help.
            Log::warning('PayPal capture webhook could not complete a checkout.', [
                'order' => $orderId,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    /** @param  array<string, mixed>  $resource */
    private function captureFailed(array $resource): void
    {
        $payment = $this->paymentByOrder($this->text($resource, 'supplementary_data.related_ids.order_id'));

        if ($payment !== null && ! $payment->isSettled()) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => __('PayPal declined the payment.'),
            ]);
        }
    }

    /** @param  array<string, mixed>  $resource */
    private function refunded(array $resource): void
    {
        $payment = $this->paymentByCapture($this->captureFromLinks($resource));

        if ($payment !== null) {
            $this->refunds->record($payment, new RefundedPayment(
                $this->text($resource, 'id'),
                $this->cents(data_get($resource, 'amount.value')),
            ));
        }
    }

    /**
     * Money taken back by PayPal, usually a chargeback from the buyer's
     * card issuer.
     *
     * @param  array<string, mixed>  $resource
     */
    private function reversed(array $resource): void
    {
        $payment = $this->paymentByCapture($this->text($resource, 'id'));

        if ($payment === null) {
            return;
        }

        $payment->update(['status' => Payment::STATUS_REVERSED]);
        $reference = $payment->order->reference ?? $payment->uuid;

        $this->tellSeller($payment, 'reversed', __('PayPal reversed the payment for :reference', ['reference' => $reference]), __(
            'PayPal took back :amount, usually because the buyer’s card issuer upheld a chargeback. Check the case in your PayPal account.',
            ['amount' => $payment->amount()->format()],
        ));

        StaffAlerts::paymentProblem($payment, __('Payment reversed'), __('PayPal reversed :amount on :reference.', [
            'amount' => $payment->amount()->format(),
            'reference' => $reference,
        ]));
    }

    /** @param  array<string, mixed>  $resource */
    private function disputed(array $resource): void
    {
        $transactions = data_get($resource, 'disputed_transactions', []);

        foreach (is_array($transactions) ? $transactions : [] as $transaction) {
            $payment = $this->paymentByCapture(is_array($transaction) ? (string) ($transaction['seller_transaction_id'] ?? '') : '');

            if ($payment === null || $payment->disputed_at !== null) {
                continue;
            }

            $payment->update(['disputed_at' => now()]);
            $reference = $payment->order->reference ?? $payment->uuid;

            $this->tellSeller($payment, 'disputed', __('A buyer opened a PayPal case on :reference', ['reference' => $reference]), __(
                'Reply in PayPal’s Resolution Center: PayPal decides the case. Tracking that shows delivery is your strongest answer.',
            ));

            StaffAlerts::paymentProblem($payment, __('PayPal case opened'), __('A buyer opened a PayPal case on :reference (:amount).', [
                'reference' => $reference,
                'amount' => $payment->amount()->format(),
            ]));
        }
    }

    private function tellSeller(Payment $payment, string $type, string $title, string $body): void
    {
        $payment->seller?->account?->notify(new PaymentUpdate(
            $type,
            $title,
            $body,
            $payment->order !== null ? route('seller.orders.show', $payment->order) : route('seller.payments'),
            'alert',
        ));
    }

    private function paymentByOrder(string $providerOrderId): ?Payment
    {
        return $providerOrderId === '' ? null : Payment::query()
            ->where('provider_order_id', $providerOrderId)
            ->latest('id')
            ->first();
    }

    private function paymentByCapture(string $captureId): ?Payment
    {
        return $captureId === '' ? null : Payment::query()->where('provider_capture_id', $captureId)->first();
    }

    /**
     * A refund points at its capture through the `up` link.
     *
     * @param  array<string, mixed>  $resource
     */
    private function captureFromLinks(array $resource): string
    {
        foreach ((array) ($resource['links'] ?? []) as $link) {
            $href = is_array($link) && ($link['rel'] ?? null) === 'up' ? (string) ($link['href'] ?? '') : '';

            if (str_contains($href, '/captures/')) {
                return Str::afterLast($href, '/');
            }
        }

        return '';
    }

    /** @param  array<string, mixed>  $data */
    private function text(array $data, string $path): string
    {
        $value = data_get($data, $path);

        return is_string($value) ? $value : '';
    }

    private function cents(mixed $value): int
    {
        return is_numeric($value) ? (int) round(((float) $value) * 100) : 0;
    }
}
