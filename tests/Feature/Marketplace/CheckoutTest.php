<?php

use App\Actions\Sellers\DecideSellerStanding;
use App\Jobs\PushTrackingToPayPal;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Notifications\PaymentUpdate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Paying for an order, clicked through the local PayPal simulator.
 *
 * The simulator runs the same actions the real PayPal driver does, so
 * these pin the flow itself: the buyer pays what the order says, the
 * commission goes to the platform, the order is paid only when PayPal
 * confirms it, and no path charges twice or captures someone else's.
 */
beforeEach(function (): void {
    config(['verum_payments.driver' => 'fake']);
    Notification::fake();

    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create([
        'seller_account_id' => $this->account->id,
        'paypal_merchant_id' => 'SIMTEST0001',
        'paypal_connected_at' => now(),
    ]);
    $this->buyer = Buyer::factory()->create();

    $this->order = Order::factory()->for($this->seller)->for($this->buyer)->create([
        'subtotal_cents' => 12_300,
        'shipping_cents' => 0,
        'total_cents' => 12_300,
        'commission_cents' => 1_230,
    ]);
});

function payFor(Order $order, string $decision = 'approve'): TestResponse
{
    $toPayPal = test()->actingAs($order->buyer, 'buyer')->post(route('buyer.orders.pay', $order))->assertRedirect();

    return test()->get(simulatePayPal($toPayPal, $decision));
}

it('marks the order paid once PayPal takes the money, with the commission to the platform', function (): void {
    payFor($this->order)
        ->assertRedirect(route('buyer.orders.show', $this->order))
        ->assertSessionHas('status');

    $payment = $this->order->payments()->sole();

    expect($this->order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and($payment->status)->toBe(Payment::STATUS_COMPLETED)
        ->and($payment->amount_cents)->toBe(12_300)
        ->and($payment->fee_cents)->toBe(1_230);

    Notification::assertSentTo($this->buyer, PaymentUpdate::class);
    Notification::assertSentTo($this->account, PaymentUpdate::class);
});

it('charges nothing when the card is declined, and the order still waits', function (): void {
    payFor($this->order, 'decline')->assertSessionHas('error');

    expect($this->order->fresh()->status)->toBe(Order::STATUS_AWAITING_PAYMENT)
        ->and($this->order->payments()->sole()->status)->toBe(Payment::STATUS_FAILED);
});

it('charges nothing when the buyer backs out in PayPal', function (): void {
    payFor($this->order, 'cancel')->assertSessionHas('info');

    expect($this->order->fresh()->status)->toBe(Order::STATUS_AWAITING_PAYMENT);
});

it('does not take the money twice when the buyer comes back twice', function (): void {
    $toPayPal = $this->actingAs($this->buyer, 'buyer')->post(route('buyer.orders.pay', $this->order));
    $back = simulatePayPal($toPayPal);

    $this->get($back)->assertSessionHas('status');
    $this->get($back)->assertSessionHas('status');

    expect($this->order->payments()->where('status', Payment::STATUS_COMPLETED)->count())->toBe(1);
});

it('will not capture a payment that belongs to another order', function (): void {
    $other = Order::factory()->for($this->seller)->for($this->buyer)->create();
    $toPayPal = $this->actingAs($this->buyer, 'buyer')->post(route('buyer.orders.pay', $other));
    $token = Str::after(simulatePayPal($toPayPal), 'token=');

    $this->get(route('buyer.orders.paid', ['order' => $this->order, 'token' => Str::before($token, '&')]))
        ->assertNotFound();

    expect($this->order->fresh()->status)->toBe(Order::STATUS_AWAITING_PAYMENT);
});

it('lets only the buyer pay for their order', function (): void {
    $this->actingAs(Buyer::factory()->create(), 'buyer')
        ->post(route('buyer.orders.pay', $this->order))
        ->assertForbidden();
});

it('cannot be paid until the seller has connected PayPal', function (): void {
    $this->seller->update(['paypal_merchant_id' => null, 'paypal_connected_at' => null]);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.orders.show', $this->order))
        ->assertSee('still setting up PayPal')
        ->assertDontSee('Pay with PayPal');

    $this->post(route('buyer.orders.pay', $this->order))->assertSessionHas('error');

    expect(Payment::count())->toBe(0);
});

it('takes the address from PayPal for an order that had none', function (): void {
    // As OrderFromWin leaves it: the buyer's name, nothing to post to.
    $this->order->update(['ship_to_line1' => '', 'ship_to_city' => '', 'ship_to_postcode' => null]);

    payFor($this->order);

    expect($this->order->fresh())
        ->status->toBe(Order::STATUS_PAID)
        ->ship_to_line1->toBe('1 Main St')
        ->ship_to_city->toBe('San Jose');
});

it('refunds the buyer through PayPal when the seller cancels a paid order', function (): void {
    payFor($this->order);
    auth()->guard('buyer')->logout();

    $this->actingAs($this->account, 'seller')
        ->put(route('seller.orders.update', $this->order), ['action' => 'cancelled', 'reason' => 'Stone cracked'])
        ->assertSessionHas('status');

    expect($this->order->fresh()->status)->toBe(Order::STATUS_CANCELLED)
        ->and($this->order->payments()->sole())
        ->status->toBe(Payment::STATUS_REFUNDED)
        ->refunded_cents->toBe(12_300);
});

it('gives PayPal the tracking number when the seller posts it', function (): void {
    Queue::fake();
    payFor($this->order);
    auth()->guard('buyer')->logout();

    $this->actingAs($this->account, 'seller')
        ->put(route('seller.orders.update', $this->order), [
            'action' => 'dispatched',
            'tracking_carrier' => 'dhl',
            'tracking_number' => 'JD0146000012',
        ])
        ->assertSessionHas('status');

    Queue::assertPushed(PushTrackingToPayPal::class, fn (PushTrackingToPayPal $job): bool => $job->orderId === $this->order->id);
});

it('links the buyer to the carrier’s own tracking page, number encoded', function (): void {
    $this->order->update([
        'status' => Order::STATUS_DISPATCHED,
        'tracking_carrier' => 'fedex',
        'tracking_number' => '7712 3456 7890',
    ]);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.orders.show', $this->order))
        ->assertSee('https://www.fedex.com/fedextrack/?trknbr=7712%203456%207890', false)
        ->assertSee('Track parcel');
});

it('refuses a tracking number that is not one', function (): void {
    $this->order->update(['status' => Order::STATUS_PAID]);

    $this->actingAs($this->account, 'seller')
        ->put(route('seller.orders.update', $this->order), [
            'action' => 'dispatched',
            'tracking_carrier' => 'dhl',
            'tracking_number' => '<script>',
        ])
        ->assertSessionHasErrors('tracking_number');
});

it('hides the simulator whenever real PayPal is the driver', function (): void {
    $toPayPal = $this->actingAs($this->buyer, 'buyer')->post(route('buyer.orders.pay', $this->order));
    $session = Str::afterLast((string) $toPayPal->headers->get('Location'), '/');

    config(['verum_payments.driver' => 'paypal']);

    $this->get(route('payments.simulator', $session))->assertNotFound();
});

it('shows the simulator with what is paid, to whom, and the commission', function (): void {
    $toPayPal = $this->actingAs($this->buyer, 'buyer')->post(route('buyer.orders.pay', $this->order));

    $this->get((string) $toPayPal->headers->get('Location'))
        ->assertOk()
        ->assertSee('no real money')
        ->assertSee('$123')
        ->assertSee('$12.30')
        ->assertSee('Decline the card');
});

it('refunds paid orders through PayPal when the seller’s store is closed', function (): void {
    payFor($this->order);

    app(DecideSellerStanding::class)->close($this->account, Admin::factory()->create());

    expect($this->order->fresh()->status)->toBe(Order::STATUS_CANCELLED)
        ->and($this->order->payments()->sole()->status)->toBe(Payment::STATUS_REFUNDED);
});
