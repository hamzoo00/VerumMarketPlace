<?php

namespace App\Actions\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Seller;
use App\Support\Payments\PaymentFailed;
use Illuminate\Support\Str;

/**
 * Connecting a seller's own PayPal account, so buyers can pay them.
 *
 * The seller is sent to PayPal with a one-time token and comes back with
 * it and their merchant id. The token must match the one stored for
 * this storefront, so a return link copied from someone else cannot
 * attach their account here — and PayPal is asked whether the account
 * can actually be paid before anything is saved.
 */
class ConnectPayPal
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /** @throws PaymentFailed */
    public function start(Seller $seller): string
    {
        $token = Str::random(40);

        $seller->update(['paypal_onboarding_token' => $token]);

        return $this->gateway->onboardingUrl($token, route('seller.payments.connected'));
    }

    /** @throws PaymentFailed */
    public function finish(Seller $seller, string $token, string $merchantId): Seller
    {
        $expected = (string) $seller->paypal_onboarding_token;

        if ($expected === '' || ! hash_equals($expected, $token)) {
            throw new PaymentFailed(__('That PayPal link has expired. Please connect again.'));
        }

        if (preg_match('/^[A-Z0-9]{5,32}$/', $merchantId) !== 1) {
            throw new PaymentFailed(__('PayPal did not send back an account. Please connect again.'));
        }

        if (! $this->gateway->merchantCanReceive($merchantId)) {
            throw new PaymentFailed(__('PayPal has not finished setting up that account. Confirm your email address with PayPal, then connect again.'));
        }

        $seller->update([
            'paypal_merchant_id' => $merchantId,
            'paypal_connected_at' => now(),
            'paypal_onboarding_token' => null,
        ]);

        return $seller->refresh();
    }

    /**
     * Stop taking payments into that account. Orders already paid are
     * untouched; ones awaiting payment wait until another is connected.
     */
    public function disconnect(Seller $seller): Seller
    {
        $seller->update([
            'paypal_merchant_id' => null,
            'paypal_connected_at' => null,
            'paypal_onboarding_token' => null,
        ]);

        return $seller->refresh();
    }
}
