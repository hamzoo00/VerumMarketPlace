<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Seller;
use App\Support\Payments\CapturedPayment;
use App\Support\Payments\GatewayRedirect;
use App\Support\Payments\PaymentFailed;
use App\Support\Payments\RefundedPayment;
use App\Support\Payments\SubscriptionState;
use Illuminate\Http\Request;

/**
 * Everything the app asks of PayPal.
 *
 * Two implementations, chosen by PAYMENTS_DRIVER: PayPalGateway talks to
 * the real API (sandbox or live), FakePaymentGateway simulates it inside
 * the app. Actions depend on this contract only, so the flow a developer
 * clicks through locally is the same code that runs in production.
 *
 * Every method throws PaymentFailed with a sentence fit to show the
 * person who clicked, never PayPal's raw error.
 */
interface PaymentGateway
{
    /** `paypal` or `fake`, stored on each payment row. */
    public function name(): string;

    /**
     * Where to send a seller to connect their PayPal account. PayPal
     * returns them to `$returnUrl` with `$token` and their merchant id.
     *
     * @throws PaymentFailed
     */
    public function onboardingUrl(string $token, string $returnUrl): string;

    /**
     * Can this merchant be paid yet — consented, email confirmed?
     *
     * @throws PaymentFailed
     */
    public function merchantCanReceive(string $merchantId): bool;

    /**
     * Open a checkout paying the seller, with the commission to the
     * platform. The buyer is sent to the returned URL to approve it.
     *
     * @throws PaymentFailed
     */
    public function createCheckout(Payment $payment, Order $order, string $returnUrl, string $cancelUrl): GatewayRedirect;

    /**
     * Take the money for an approved checkout. Asking again for one
     * already captured answers with that capture, never a second charge.
     *
     * @throws PaymentFailed
     */
    public function capture(string $providerOrderId): CapturedPayment;

    /**
     * Give some or all of a captured payment back to the buyer. A full
     * refund returns the platform's commission too.
     *
     * @throws PaymentFailed
     */
    public function refund(Payment $payment, ?int $amountCents = null): RefundedPayment;

    /**
     * Start the monthly premium subscription for a seller.
     *
     * @throws PaymentFailed
     */
    public function createSubscription(Seller $seller, string $requestId, string $returnUrl, string $cancelUrl): GatewayRedirect;

    /** @throws PaymentFailed */
    public function subscription(string $subscriptionId): SubscriptionState;

    /** @throws PaymentFailed */
    public function cancelSubscription(string $subscriptionId, string $reason): void;

    /**
     * Tell PayPal the parcel's tracking number, which protects the
     * seller if a buyer claims it never arrived.
     *
     * @throws PaymentFailed
     */
    public function addTracking(Payment $payment, string $carrierKey, string $trackingNumber): void;

    /**
     * The webhook event, if it really came from PayPal; null otherwise.
     *
     * @return array<string, mixed>|null
     */
    public function verifyWebhook(Request $request): ?array;
}
