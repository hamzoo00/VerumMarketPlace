<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Money;

/**
 * What a code is worth on a given basket.
 *
 * Pure decisions. Every rule that could let a buyer pay less than they
 * should lives here and nowhere else, so the cart preview and the eventual
 * checkout cannot disagree.
 *
 * The discount is always recomputed from the coupon row — never taken
 * from anything the browser sends.
 */
final readonly class CouponQuote
{
    private function __construct(
        public ?Coupon $coupon,
        public int $discountCents,
        public bool $freeShipping,
        public ?string $problem,
    ) {}

    public static function rejected(string $problem): self
    {
        return new self(null, 0, false, $problem);
    }

    /**
     * @param  int  $subtotalCents  The seller's items only — a code is
     *                              scoped to its own storefront.
     * @param  int  $redeemedByBuyer  How many times this buyer already used it.
     */
    public static function apply(
        Coupon $coupon,
        int $subtotalCents,
        int $redeemedByBuyer = 0,
    ): self {
        if (! $coupon->isLive()) {
            return self::rejected(__('That code is no longer available.'));
        }

        if ($redeemedByBuyer >= $coupon->per_buyer_limit) {
            return self::rejected(__('You have already used that code.'));
        }

        $minimum = (int) ($coupon->min_spend_cents ?? 0);

        if ($subtotalCents < $minimum) {
            return self::rejected(__('Spend :amount to use that code.', [
                'amount' => (new Money($minimum))->format(),
            ]));
        }

        if ($coupon->type === Coupon::TYPE_FREE_SHIPPING) {
            return new self($coupon, 0, true, null);
        }

        $discount = $coupon->type === Coupon::TYPE_PERCENT
            ? (int) round($subtotalCents * (int) $coupon->percent / 100)
            : (int) $coupon->amount_cents;

        // Never more than the basket: a fixed code larger than the order
        // would otherwise hand money back.
        return new self($coupon, min($discount, $subtotalCents), false, null);
    }

    public function isValid(): bool
    {
        return $this->problem === null;
    }

    public function discount(string $currency = 'USD'): Money
    {
        return new Money($this->discountCents, $currency);
    }
}
