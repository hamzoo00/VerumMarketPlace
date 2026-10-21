<?php

use App\Actions\Coupons\SaveCoupon;
use App\Models\Coupon;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Support\CouponQuote;

/**
 * Discount codes.
 *
 * Every rule here decides how much someone pays, so the ways a code could
 * be worth more than it should are each pinned.
 */
beforeEach(function (): void {
    $this->seller = Seller::factory()->create();
    $this->save = app(SaveCoupon::class);
});

it('stores a code upper-cased so matching is predictable', function (): void {
    $coupon = $this->save->handle($this->seller, [
        'code' => 'welcome10', 'type' => Coupon::TYPE_PERCENT,
        'percent' => 10, 'per_buyer_limit' => 1,
    ]);

    expect($coupon->code)->toBe('WELCOME10');
});

it('takes a percentage off the basket', function (): void {
    $coupon = $this->save->handle($this->seller, [
        'code' => 'TEN', 'type' => Coupon::TYPE_PERCENT,
        'percent' => 10, 'per_buyer_limit' => 1,
    ]);

    expect(CouponQuote::apply($coupon, 10_000)->discountCents)->toBe(1_000);
});

it('never discounts more than the basket', function (): void {
    $coupon = $this->save->handle($this->seller, [
        'code' => 'BIG', 'type' => Coupon::TYPE_FIXED,
        'amount' => 500, 'per_buyer_limit' => 1,
    ]);

    // A $500 code on a $100 order must not hand money back.
    expect(CouponQuote::apply($coupon, 10_000)->discountCents)->toBe(10_000);
});

it('enforces a minimum spend', function (): void {
    $coupon = $this->save->handle($this->seller, [
        'code' => 'MIN50', 'type' => Coupon::TYPE_PERCENT,
        'percent' => 10, 'min_spend' => 50, 'per_buyer_limit' => 1,
    ]);

    expect(CouponQuote::apply($coupon, 3_000)->isValid())->toBeFalse()
        ->and(CouponQuote::apply($coupon, 5_000)->isValid())->toBeTrue();
});

it('enforces the per-buyer limit', function (): void {
    $coupon = $this->save->handle($this->seller, [
        'code' => 'ONCE', 'type' => Coupon::TYPE_PERCENT,
        'percent' => 10, 'per_buyer_limit' => 1,
    ]);

    expect(CouponQuote::apply($coupon, 10_000, redeemedByBuyer: 1)->isValid())->toBeFalse();
});

it('refuses an expired or exhausted code', function (): void {
    $expired = $this->save->handle($this->seller, [
        'code' => 'GONE', 'type' => Coupon::TYPE_PERCENT, 'percent' => 10,
        'per_buyer_limit' => 1, 'ends_at' => now()->subDay(),
    ]);

    $used = $this->save->handle($this->seller, [
        'code' => 'USEDUP', 'type' => Coupon::TYPE_PERCENT, 'percent' => 10,
        'per_buyer_limit' => 1, 'max_redemptions' => 1,
    ]);
    $used->update(['used_count' => 1]);

    expect(CouponQuote::apply($expired, 10_000)->isValid())->toBeFalse()
        ->and(CouponQuote::apply($used->fresh(), 10_000)->isValid())->toBeFalse();
});

it('clears the value that does not apply to the type', function (): void {
    $coupon = $this->save->handle($this->seller, [
        'code' => 'FREESHIP', 'type' => Coupon::TYPE_FREE_SHIPPING,
        'percent' => 25, 'amount' => 10, 'per_buyer_limit' => 1,
    ]);

    expect($coupon->percent)->toBeNull()
        ->and($coupon->amount_cents)->toBeNull()
        ->and(CouponQuote::apply($coupon, 10_000)->freeShipping)->toBeTrue();
});

it('lets two sellers use the same code', function (): void {
    $other = Seller::factory()->create();

    $this->save->handle($this->seller, [
        'code' => 'SHARED', 'type' => Coupon::TYPE_PERCENT, 'percent' => 10, 'per_buyer_limit' => 1,
    ]);

    expect(fn () => $this->save->handle($other, [
        'code' => 'SHARED', 'type' => Coupon::TYPE_PERCENT, 'percent' => 5, 'per_buyer_limit' => 1,
    ]))->not->toThrow(Exception::class);
});

it('stops one seller deleting another sellers code', function (): void {
    $coupon = $this->save->handle($this->seller, [
        'code' => 'MINE', 'type' => Coupon::TYPE_PERCENT, 'percent' => 10, 'per_buyer_limit' => 1,
    ]);

    $intruder = SellerAccount::factory()->create(['approved_at' => now()]);
    Seller::factory()->create(['seller_account_id' => $intruder->id]);

    $this->actingAs($intruder, 'seller')
        ->delete(route('seller.coupons.destroy', $coupon))
        ->assertForbidden();

    expect(Coupon::find($coupon->id))->not->toBeNull();
});
