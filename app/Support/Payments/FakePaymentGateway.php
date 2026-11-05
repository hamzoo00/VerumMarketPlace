<?php

namespace App\Support\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Seller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PayPal, simulated inside the app (PAYMENTS_DRIVER=fake).
 *
 * Every step that would send someone to PayPal sends them to a local
 * page instead (FakePayPalController) with Approve, Cancel and — for a
 * checkout — Decline. What that page decides is what capture() and
 * subscription() then report, so the whole flow can be clicked through
 * with no PayPal account and nothing leaving the machine.
 *
 * Sessions live in the cache and the return addresses are stored here,
 * never taken from the simulator's form, so the page cannot be used to
 * redirect anywhere else. Refused in production by the container
 * binding in AppServiceProvider.
 */
final class FakePaymentGateway implements PaymentGateway
{
    /** Simulated merchant ids start with this, so they are recognisable. */
    public const MERCHANT_PREFIX = 'SIM';

    private const TTL_HOURS = 6;

    /** The address PayPal's own sandbox buyers carry. */
    private const SANDBOX_ADDRESS = [
        'name' => 'Sandbox Buyer',
        'line1' => '1 Main St',
        'line2' => null,
        'city' => 'San Jose',
        'region' => 'CA',
        'postcode' => '95131',
        'country' => 'US',
    ];

    public function name(): string
    {
        return 'fake';
    }

    public function onboardingUrl(string $token, string $returnUrl): string
    {
        $merchant = self::MERCHANT_PREFIX.Str::upper(Str::random(10));

        return route('payments.simulator', $this->open('onboarding', [
            'title' => __('Connect your PayPal account'),
            'amount_cents' => null,
            'approve' => $this->withQuery($returnUrl, ['merchantId' => $token, 'merchantIdInPayPal' => $merchant]),
            'cancel' => $returnUrl,
        ]));
    }

    public function merchantCanReceive(string $merchantId): bool
    {
        return str_starts_with($merchantId, self::MERCHANT_PREFIX);
    }

    public function createCheckout(Payment $payment, Order $order, string $returnUrl, string $cancelUrl): GatewayRedirect
    {
        $id = 'SIM-'.Str::upper(Str::random(14));

        $this->open('checkout', [
            'title' => __('Pay for :reference', ['reference' => $order->reference]),
            'amount_cents' => (int) $payment->amount_cents,
            'fee_cents' => (int) $payment->fee_cents,
            'currency' => $payment->currency,
            'payee' => $order->seller?->name,
            'has_address' => $order->hasAddress(),
            'approve' => $this->withQuery($returnUrl, ['token' => $id, 'PayerID' => 'SIMBUYER']),
            'cancel' => $this->withQuery($cancelUrl, ['token' => $id]),
        ], $id);

        return new GatewayRedirect($id, route('payments.simulator', $id));
    }

    public function capture(string $providerOrderId): CapturedPayment
    {
        $session = $this->session($providerOrderId)
            ?? throw new PaymentFailed(__('That payment has expired. Please start again.'));

        throw_if($session['status'] === 'declined', new PaymentFailed(
            __('PayPal declined that payment method. Please try again and choose another one.'),
        ));

        throw_unless(in_array($session['status'], ['approved', 'captured'], true), new PaymentFailed(
            __('The payment was not approved in PayPal. Please try again.'),
        ));

        $captureId = $session['capture_id'] ?? 'SIMCAP'.Str::upper(Str::random(11));
        $this->put($providerOrderId, [...$session, 'status' => 'captured', 'capture_id' => $captureId]);

        return new CapturedPayment(
            CapturedPayment::COMPLETED,
            $captureId,
            (int) $session['amount_cents'],
            (int) ($session['fee_cents'] ?? 0),
            ($session['has_address'] ?? true) ? null : self::SANDBOX_ADDRESS,
        );
    }

    public function refund(Payment $payment, ?int $amountCents = null): RefundedPayment
    {
        return new RefundedPayment(
            'SIMREF'.Str::upper(Str::random(11)),
            min($amountCents ?? (int) $payment->amount_cents, (int) $payment->amount_cents),
        );
    }

    public function createSubscription(Seller $seller, string $requestId, string $returnUrl, string $cancelUrl): GatewayRedirect
    {
        $id = 'I-SIM'.Str::upper(Str::random(10));

        $this->open('subscription', [
            'title' => __('Subscribe to :plan', ['plan' => __(config('verum_payments.premium.name'))]),
            'amount_cents' => (int) config('verum_payments.premium.price_cents'),
            'currency' => (string) config('verum_payments.currency'),
            'per' => __('month'),
            'approve' => $this->withQuery($returnUrl, ['subscription_id' => $id]),
            'cancel' => $cancelUrl,
        ], $id);

        return new GatewayRedirect($id, route('payments.simulator', $id));
    }

    public function subscription(string $subscriptionId): SubscriptionState
    {
        $session = $this->session($subscriptionId)
            ?? throw new PaymentFailed(__('That subscription has expired. Please start again.'));

        return new SubscriptionState(
            $subscriptionId,
            match ($session['status']) {
                'approved' => SubscriptionState::ACTIVE,
                'cancelled' => 'cancelled',
                default => 'approval_pending',
            },
            $session['status'] === 'approved' ? CarbonImmutable::now()->addMonth() : null,
        );
    }

    public function cancelSubscription(string $subscriptionId, string $reason): void
    {
        $session = $this->session($subscriptionId);

        if ($session !== null) {
            $this->put($subscriptionId, [...$session, 'status' => 'cancelled']);
        }
    }

    public function addTracking(Payment $payment, string $carrierKey, string $trackingNumber): void
    {
        Log::info('Simulated PayPal tracking.', ['payment' => $payment->uuid, 'carrier' => $carrierKey]);
    }

    /** The simulator never sends webhooks; renewals and disputes need the sandbox. */
    public function verifyWebhook(Request $request): ?array
    {
        return null;
    }

    /**
     * One simulated PayPal page, as the simulator shows it.
     *
     * @return array<string, mixed>|null
     */
    public function session(string $id): ?array
    {
        $session = Cache::get($this->key($id));

        return is_array($session) ? $session : null;
    }

    /**
     * Record what the person chose and say where PayPal would send them.
     * Decline only exists for a checkout, where a card can be refused.
     */
    public function decide(string $id, string $decision): ?string
    {
        $session = $this->session($id);

        if ($session === null || $session['status'] !== 'created') {
            return null;
        }

        $allowed = $session['kind'] === 'checkout' ? ['approve', 'decline', 'cancel'] : ['approve', 'cancel'];

        if (! in_array($decision, $allowed, true)) {
            return null;
        }

        $status = ['approve' => 'approved', 'decline' => 'declined', 'cancel' => 'cancelled'][$decision];
        $this->put($id, [...$session, 'status' => $status]);

        // A declined card still goes back to the shop, where capturing fails.
        return $decision === 'cancel' ? $session['cancel'] : $session['approve'];
    }

    /** @param  array<string, mixed>  $data */
    private function open(string $kind, array $data, ?string $id = null): string
    {
        $id ??= 'SIM-'.Str::upper(Str::random(14));

        $this->put($id, [...$data, 'kind' => $kind, 'status' => 'created']);

        return $id;
    }

    /** @param  array<string, mixed>  $session */
    private function put(string $id, array $session): void
    {
        Cache::put($this->key($id), $session, now()->addHours(self::TTL_HOURS));
    }

    private function key(string $id): string
    {
        return 'verum:payments:simulator:'.$id;
    }

    /** @param  array<string, string>  $query */
    private function withQuery(string $url, array $query): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query);
    }
}
