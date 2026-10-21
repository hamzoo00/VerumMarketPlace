<?php

namespace App\Actions\Coupons;

use App\Models\Coupon;
use App\Models\Seller;

/**
 * Creates or updates one discount code.
 *
 * The only place decimals typed by a seller become integer minor units,
 * and the only place the fields that do not apply to a type are cleared —
 * a percent code must not keep a stale fixed amount.
 */
class SaveCoupon
{
    /** @param  array<string, mixed>  $data */
    public function handle(Seller $seller, array $data, ?Coupon $coupon = null): Coupon
    {
        $type = $data['type'];

        $attributes = [
            'code' => $data['code'],
            'type' => $type,
            'percent' => $type === Coupon::TYPE_PERCENT ? (int) $data['percent'] : null,
            'amount_cents' => $type === Coupon::TYPE_FIXED ? $this->cents($data['amount']) : null,
            'min_spend_cents' => $this->cents($data['min_spend'] ?? null),
            'max_redemptions' => $this->nullableInt($data['max_redemptions'] ?? null),
            'per_buyer_limit' => (int) ($data['per_buyer_limit'] ?? 1),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];

        if ($coupon === null) {
            return $seller->coupons()->create($attributes);
        }

        $coupon->update($attributes);

        return $coupon->refresh();
    }

    private function cents(mixed $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return (int) round((float) $amount * 100);
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
