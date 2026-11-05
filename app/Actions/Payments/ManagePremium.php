<?php

namespace App\Actions\Payments;

use App\Actions\Premium\DecidePremium;
use App\Contracts\PaymentGateway;
use App\Models\Payment;
use App\Models\Seller;
use App\Notifications\PaymentUpdate;
use App\Support\Payments\PaymentFailed;
use App\Support\Payments\SubscriptionState;
use App\Support\PremiumStatus;
use Illuminate\Support\Str;

/**
 * Premium as a monthly PayPal subscription, paid to the platform.
 *
 * Premium itself is still the date range DecidePremium owns; a
 * subscription only keeps moving its end to the next billing date (plus
 * a few days' grace). Cancelling stops the renewals, not what was
 * already paid for — premium runs to the end of that month.
 *
 * PayPal's word decides, never the return link: the subscription is
 * read back from PayPal before premium is granted or extended.
 */
class ManagePremium
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly DecidePremium $premium,
    ) {}

    /** @throws PaymentFailed */
    public function start(Seller $seller): string
    {
        if ($seller->hasPremiumSubscription()) {
            throw new PaymentFailed(__('Your premium subscription is already running.'));
        }

        if (PremiumStatus::isPremium($seller) && $seller->premium_until === null) {
            throw new PaymentFailed(__('Your store already has premium standing from our team.'));
        }

        $redirect = $this->gateway->createSubscription(
            $seller,
            (string) Str::uuid(),
            route('seller.payments.subscribed'),
            route('seller.payments'),
        );

        $seller->update([
            'premium_subscription_id' => $redirect->id,
            'premium_subscription_status' => 'approval_pending',
        ]);

        return $redirect->url;
    }

    /**
     * The seller came back from PayPal. Only the subscription this store
     * started counts, so a link carrying another one changes nothing.
     *
     * @throws PaymentFailed
     */
    public function confirm(Seller $seller, string $subscriptionId): Seller
    {
        $expected = (string) $seller->premium_subscription_id;

        if ($expected === '' || ! hash_equals($expected, $subscriptionId)) {
            throw new PaymentFailed(__('That subscription does not belong to your store.'));
        }

        return $this->sync($seller, $this->gateway->subscription($subscriptionId));
    }

    /** BILLING.SUBSCRIPTION.ACTIVATED, for a seller who closed the tab. */
    public function activated(string $subscriptionId): void
    {
        $seller = $this->sellerFor($subscriptionId);

        if ($seller !== null) {
            $this->sync($seller, $this->gateway->subscription($subscriptionId));
        }
    }

    /** PAYMENT.SALE.COMPLETED: a month was paid. Recorded once per sale. */
    public function renewed(string $subscriptionId, string $saleId, int $amountCents, string $currency): void
    {
        $seller = $this->sellerFor($subscriptionId);

        if ($seller === null || $saleId === '') {
            return;
        }

        Payment::query()->firstOrCreate(['provider_capture_id' => $saleId], [
            'purpose' => Payment::PURPOSE_PREMIUM,
            'seller_id' => $seller->id,
            'provider' => $this->gateway->name(),
            'provider_order_id' => $subscriptionId,
            'status' => Payment::STATUS_COMPLETED,
            'amount_cents' => $amountCents,
            'currency' => $currency,
            'completed_at' => now(),
        ]);

        $this->sync($seller, $this->gateway->subscription($subscriptionId));
    }

    /** Cancelled, suspended or expired in PayPal. */
    public function ended(string $subscriptionId, string $status): void
    {
        $seller = $this->sellerFor($subscriptionId);

        if ($seller === null) {
            return;
        }

        $seller->update(['premium_subscription_status' => $status]);

        $seller->account?->notify(new PaymentUpdate(
            'premium.ended',
            __('Your premium subscription has stopped'),
            $seller->premium_until?->isFuture()
                ? __('PayPal reports the subscription as :status. Premium stays on until :date, the end of what you paid for.', [
                    'status' => $status,
                    'date' => $seller->premium_until->format('j M Y'),
                ])
                : __('PayPal reports the subscription as :status, so premium has ended.', ['status' => $status]),
            route('seller.payments'),
            'badge',
        ));
    }

    /** BILLING.SUBSCRIPTION.PAYMENT.FAILED — PayPal will try again. */
    public function paymentFailed(string $subscriptionId): void
    {
        $this->sellerFor($subscriptionId)?->account?->notify(new PaymentUpdate(
            'premium.failed',
            __('This month’s premium payment did not go through'),
            __('PayPal could not take the premium payment. Check the card or balance in your PayPal account; PayPal will try again.'),
            route('seller.payments'),
            'alert',
        ));
    }

    /**
     * Stop renewing. Premium already paid for runs to its end.
     *
     * @throws PaymentFailed
     */
    public function cancel(Seller $seller): Seller
    {
        $id = (string) $seller->premium_subscription_id;

        if ($id === '') {
            throw new PaymentFailed(__('There is no premium subscription to cancel.'));
        }

        $this->gateway->cancelSubscription($id, 'Cancelled by the seller');

        $seller->update(['premium_subscription_status' => 'cancelled']);

        return $seller->refresh();
    }

    private function sync(Seller $seller, SubscriptionState $state): Seller
    {
        $seller->update(['premium_subscription_status' => $state->status]);

        if (! $state->isActive() || $state->nextBillingAt === null) {
            return $seller->refresh();
        }

        // Premium granted by the team with no end is left as it is.
        if (PremiumStatus::isPremium($seller) && $seller->premium_until === null) {
            return $seller->refresh();
        }

        $until = $state->nextBillingAt->addDays((int) config('verum_payments.premium.grace_days', 3));

        // A late or repeated event must never shorten what was paid for.
        if ($seller->premium_until !== null && $seller->premium_until->greaterThanOrEqualTo($until) && PremiumStatus::isPremium($seller)) {
            return $seller->refresh();
        }

        return $this->premium->subscribed($seller, $until);
    }

    private function sellerFor(string $subscriptionId): ?Seller
    {
        return Seller::query()->where('premium_subscription_id', $subscriptionId)->first();
    }
}
