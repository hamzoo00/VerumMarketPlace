<?php

namespace App\Console\Commands;

use App\Models\Money;
use App\Support\Payments\PaymentFailed;
use App\Support\Payments\PayPalGateway;
use Illuminate\Console\Command;

/**
 * Creates the monthly premium plan in PayPal and prints its id.
 *
 * Run once per PayPal account — once against the sandbox, once against
 * live — and again whenever the price changes (PayPal plans have a fixed
 * price; existing subscribers keep theirs). The id goes into
 * PAYPAL_PREMIUM_PLAN_ID.
 */
class CreatePayPalPremiumPlan extends Command
{
    protected $signature = 'paypal:premium-plan';

    protected $description = 'Create the monthly premium subscription plan in PayPal';

    public function handle(PayPalGateway $paypal): int
    {
        $price = (int) config('verum_payments.premium.price_cents');
        $name = (string) config('verum_payments.premium.name');

        $this->components->info(sprintf(
            'Creating "%s" at %s a month in PayPal %s.',
            $name,
            Money::fromCents($price, (string) config('verum_payments.currency'))->format(),
            $paypal->mode(),
        ));

        try {
            $planId = $paypal->createPremiumPlan($price, $name);
        } catch (PaymentFailed $e) {
            $this->components->error($e->getMessage().' The log has PayPal\'s reason.');

            return self::FAILURE;
        }

        $this->components->info('Done. Add this to .env:');
        $this->line("PAYPAL_PREMIUM_PLAN_ID={$planId}");

        return self::SUCCESS;
    }
}
