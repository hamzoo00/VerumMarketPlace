<?php

use App\Models\Admin;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Models\SellerApplication;
use App\Notifications\AccountNoticeSent;
use App\Support\PremiumStatus;
use Illuminate\Support\Facades\Notification;

/**
 * A seller connecting PayPal and paying for premium, through the
 * simulator (simulatePayPal(), in tests/Pest.php).
 *
 * Pinned: a return link only finishes what this store started, premium
 * runs to the next billing date, and cancelling stops renewals without
 * taking away the month already paid for.
 */
beforeEach(function (): void {
    config(['verum_payments.driver' => 'fake']);
    Notification::fake();

    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
});

it('shows the payments page with what is still to do', function (): void {
    $this->actingAs($this->account, 'seller')
        ->get(route('seller.payments'))
        ->assertOk()
        ->assertSee('Test mode')
        ->assertSee('Connect PayPal')
        ->assertSee('Subscribe for');
});

it('connects the seller’s PayPal through PayPal and back', function (): void {
    $toPayPal = $this->actingAs($this->account, 'seller')->post(route('seller.payments.connect'))->assertRedirect();

    $this->get(simulatePayPal($toPayPal))
        ->assertRedirect(route('seller.payments'))
        ->assertSessionHas('status');

    expect($this->seller->fresh()->canReceivePayments())->toBeTrue();
});

it('will not attach an account from someone else’s return link', function (): void {
    $this->actingAs($this->account, 'seller')->post(route('seller.payments.connect'));

    $this->get(route('seller.payments.connected', ['merchantId' => 'not-the-token', 'merchantIdInPayPal' => 'SIMATTACKER1']))
        ->assertSessionHas('error');

    expect($this->seller->fresh()->canReceivePayments())->toBeFalse();
});

it('makes the store premium until the next billing date', function (): void {
    $toPayPal = $this->actingAs($this->account, 'seller')->post(route('seller.payments.subscribe'))->assertRedirect();

    $this->get(simulatePayPal($toPayPal))->assertSessionHas('status');

    $seller = $this->seller->fresh();

    expect(PremiumStatus::isPremium($seller))->toBeTrue()
        ->and($seller->hasPremiumSubscription())->toBeTrue()
        // A month, plus the grace that keeps a late renewal from flickering it off.
        ->and($seller->premium_until->toDateString())->toBe(now()->addMonth()->addDays(3)->toDateString());

    Notification::assertSentTo($this->account, AccountNoticeSent::class);
});

it('keeps the paid month when the subscription is cancelled', function (): void {
    $toPayPal = $this->actingAs($this->account, 'seller')->post(route('seller.payments.subscribe'));
    $this->get(simulatePayPal($toPayPal));

    $this->delete(route('seller.payments.unsubscribe'))->assertSessionHas('status');

    $seller = $this->seller->fresh();

    expect($seller->hasPremiumSubscription())->toBeFalse()
        ->and(PremiumStatus::isPremium($seller))->toBeTrue();
});

it('will not start premium from a subscription this store did not start', function (): void {
    $this->actingAs($this->account, 'seller')
        ->get(route('seller.payments.subscribed', ['subscription_id' => 'I-SOMEONEELSE']))
        ->assertSessionHas('error');

    expect(PremiumStatus::isPremium($this->seller->fresh()))->toBeFalse();
});

it('warns admins about applicants from countries PayPal does not serve', function (): void {
    SellerApplication::factory()->create(['country' => 'Pakistan']);

    $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('admin.applications'))
        ->assertOk()
        ->assertSee('No PayPal');
});
