<?php

use App\Actions\Payments\StartCheckout;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Notifications\PaymentUpdate;
use App\Notifications\StaffAlert;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

/**
 * The real PayPal driver, against a faked PayPal API.
 *
 * Pins what is sent — the seller as payee, the commission as a platform
 * fee, sandbox unless the mode is exactly `live` — and what a webhook
 * may change: nothing until PayPal confirms the signature, and each
 * event only once however often it is delivered.
 */
beforeEach(function (): void {
    config([
        'verum_payments.driver' => 'paypal',
        'services.paypal.mode' => 'sandbox',
        'services.paypal.client_id' => 'client-id',
        'services.paypal.client_secret' => 'client-secret',
        'services.paypal.webhook_id' => 'WH-1',
        'services.paypal.partner_merchant_id' => 'PARTNER1',
    ]);

    Notification::fake();

    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create([
        'seller_account_id' => $this->account->id,
        'paypal_merchant_id' => 'SELLERMERCHANT1',
        'paypal_connected_at' => now(),
    ]);
    $this->buyer = Buyer::factory()->create();
    $this->order = Order::factory()->for($this->seller)->for($this->buyer)->create([
        'subtotal_cents' => 12_300,
        'shipping_cents' => 1_500,
        'discount_cents' => 0,
        'total_cents' => 13_800,
        'commission_cents' => 1_230,
    ]);
});

/** @param  array<string, mixed>  $more */
function fakePayPal(array $more = []): void
{
    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'TOKEN', 'expires_in' => 32400]),
        '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
        ...$more,
        '*/v2/checkout/orders' => Http::response([
            'id' => 'PAYPAL-ORDER-1',
            'status' => 'PAYER_ACTION_REQUIRED',
            'links' => [['rel' => 'payer-action', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-1']],
        ], 201),
    ]);
}

/** PayPal's order once captured, as both capture and GET return it. */
function capturedOrder(string $status = 'COMPLETED'): array
{
    return [
        'id' => 'PAYPAL-ORDER-1',
        'status' => 'COMPLETED',
        'purchase_units' => [[
            'shipping' => [
                'name' => ['full_name' => 'Ana Buyer'],
                'address' => ['address_line_1' => '9 Gem Road', 'admin_area_2' => 'Galle', 'postal_code' => '80000', 'country_code' => 'LK'],
            ],
            'payments' => ['captures' => [[
                'id' => 'CAPTURE-1',
                'status' => $status,
                'amount' => ['currency_code' => 'USD', 'value' => '138.00'],
                'seller_receivable_breakdown' => ['platform_fees' => [['amount' => ['currency_code' => 'USD', 'value' => '12.30']]]],
            ]]],
        ]],
    ];
}

/** @param  array<string, mixed>  $event */
function paypalWebhook(array $event): TestResponse
{
    return test()->withHeaders([
        'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        'PAYPAL-CERT-URL' => 'https://api.sandbox.paypal.com/v1/notifications/certs/CERT',
        'PAYPAL-TRANSMISSION-ID' => 'TX-1',
        'PAYPAL-TRANSMISSION-SIG' => 'SIG',
        'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
    ])->postJson(route('webhooks.paypal'), $event);
}

function startedPayment(Order $order): Payment
{
    fakePayPal();
    app(StartCheckout::class)->handle($order);

    return $order->payments()->sole();
}

it('pays the seller directly and sends the commission to the platform', function (): void {
    fakePayPal();

    $url = app(StartCheckout::class)->handle($this->order);

    expect($url)->toBe('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-1');

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/checkout/orders')
        && $request['purchase_units'][0]['payee']['merchant_id'] === 'SELLERMERCHANT1'
        && $request['purchase_units'][0]['amount']['value'] === '138.00'
        && $request['purchase_units'][0]['amount']['breakdown']['shipping']['value'] === '15.00'
        && $request['purchase_units'][0]['payment_instruction']['platform_fees'][0]['amount']['value'] === '12.30'
        && $request->hasHeader('PayPal-Request-Id'));
});

it('uses the sandbox unless the mode is exactly live', function (string $mode, string $host): void {
    config(['services.paypal.mode' => $mode]);
    fakePayPal();

    app(StartCheckout::class)->handle($this->order);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), $host.'/v2/checkout/orders'));
})->with([
    'sandbox' => ['sandbox', 'https://api-m.sandbox.paypal.com'],
    'a typo stays in the sandbox' => ['Live ', 'https://api-m.sandbox.paypal.com'],
    'live' => ['live', 'https://api-m.paypal.com'],
]);

it('reads the capture: amount, commission and the buyer’s PayPal address', function (): void {
    $payment = startedPayment($this->order);
    $this->order->update(['ship_to_line1' => '', 'ship_to_city' => '']);

    fakePayPal(['*/v2/checkout/orders/PAYPAL-ORDER-1/capture' => Http::response(capturedOrder(), 201)]);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.orders.paid', ['order' => $this->order, 'token' => 'PAYPAL-ORDER-1']))
        ->assertSessionHas('status');

    expect($payment->fresh())
        ->status->toBe(Payment::STATUS_COMPLETED)
        ->provider_capture_id->toBe('CAPTURE-1')
        ->fee_cents->toBe(1_230)
        ->and($this->order->fresh())
        ->status->toBe(Order::STATUS_PAID)
        ->ship_to_city->toBe('Galle');
});

it('says so plainly when PayPal declines the card', function (): void {
    startedPayment($this->order);

    fakePayPal(['*/v2/checkout/orders/PAYPAL-ORDER-1/capture' => Http::response([
        'name' => 'UNPROCESSABLE_ENTITY',
        'details' => [['issue' => 'INSTRUMENT_DECLINED']],
    ], 422)]);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.orders.paid', ['order' => $this->order, 'token' => 'PAYPAL-ORDER-1']))
        ->assertSessionHas('error', 'PayPal declined that payment method. Please try again and choose another one.');

    expect($this->order->fresh()->status)->toBe(Order::STATUS_AWAITING_PAYMENT);
});

it('marks the order paid from the webhook when the buyer closed the tab', function (): void {
    startedPayment($this->order);

    fakePayPal([
        '*/v2/checkout/orders/PAYPAL-ORDER-1/capture' => Http::response([
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [['issue' => 'ORDER_ALREADY_CAPTURED']],
        ], 422),
        '*/v2/checkout/orders/PAYPAL-ORDER-1' => Http::response(capturedOrder()),
    ]);

    paypalWebhook([
        'id' => 'WH-EVENT-1',
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => ['id' => 'CAPTURE-1', 'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-ORDER-1']]],
    ])->assertNoContent();

    expect($this->order->fresh()->status)->toBe(Order::STATUS_PAID);
});

it('acts on each webhook once, however often PayPal delivers it', function (): void {
    $payment = startedPayment($this->order);
    $payment->update(['status' => Payment::STATUS_COMPLETED, 'provider_capture_id' => 'CAPTURE-1']);
    $this->order->update(['status' => Order::STATUS_PAID]);

    fakePayPal();

    $event = [
        'id' => 'WH-REFUND-1',
        'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
        'resource' => [
            'id' => 'REFUND-1',
            'amount' => ['value' => '50.00'],
            'links' => [['rel' => 'up', 'href' => 'https://api-m.sandbox.paypal.com/v2/payments/captures/CAPTURE-1']],
        ],
    ];

    paypalWebhook($event)->assertNoContent();
    paypalWebhook($event)->assertNoContent();

    expect($payment->fresh())
        ->refunded_cents->toBe(5_000)
        ->status->toBe(Payment::STATUS_PARTIALLY_REFUNDED)
        ->and(DB::table('payment_events')->where('event_id', 'WH-REFUND-1')->count())->toBe(1);
});

it('cancels an unposted order refunded in full inside PayPal', function (): void {
    $payment = startedPayment($this->order);
    $payment->update(['status' => Payment::STATUS_COMPLETED, 'provider_capture_id' => 'CAPTURE-1']);
    $this->order->update(['status' => Order::STATUS_PAID]);

    fakePayPal();

    paypalWebhook([
        'id' => 'WH-REFUND-2',
        'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
        'resource' => [
            'id' => 'REFUND-2',
            'amount' => ['value' => '138.00'],
            'links' => [['rel' => 'up', 'href' => 'https://api-m.sandbox.paypal.com/v2/payments/captures/CAPTURE-1']],
        ],
    ])->assertNoContent();

    expect($this->order->fresh()->status)->toBe(Order::STATUS_CANCELLED)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_REFUNDED);

    Notification::assertSentTo($this->buyer, PaymentUpdate::class);
});

it('records a PayPal case and tells the seller and the admins', function (): void {
    $admin = Admin::factory()->create();
    $payment = startedPayment($this->order);
    $payment->update(['status' => Payment::STATUS_COMPLETED, 'provider_capture_id' => 'CAPTURE-1']);

    fakePayPal();

    paypalWebhook([
        'id' => 'WH-DISPUTE-1',
        'event_type' => 'CUSTOMER.DISPUTE.CREATED',
        'resource' => ['id' => 'PP-D-1', 'disputed_transactions' => [['seller_transaction_id' => 'CAPTURE-1']]],
    ])->assertNoContent();

    expect($payment->fresh()->disputed_at)->not->toBeNull();

    Notification::assertSentTo($this->account, PaymentUpdate::class);
    Notification::assertSentTo($admin, StaffAlert::class);
});

it('ignores a webhook PayPal did not sign', function (): void {
    // Made directly: a second Http::fake() would not replace the first.
    $payment = Payment::factory()->for($this->order)->create(['provider_order_id' => 'PAYPAL-ORDER-1']);

    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'TOKEN', 'expires_in' => 32400]),
        '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'FAILURE']),
    ]);

    paypalWebhook([
        'id' => 'WH-FORGED',
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => ['supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-ORDER-1']]],
    ])->assertBadRequest();

    expect($payment->fresh()->status)->toBe(Payment::STATUS_CREATED)
        ->and(DB::table('payment_events')->count())->toBe(0);
});

it('refuses a webhook with no signature at all, without asking PayPal', function (): void {
    fakePayPal();

    $this->postJson(route('webhooks.paypal'), ['id' => 'X', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED'])
        ->assertBadRequest();

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'verify-webhook-signature'));
});

it('extends premium when PayPal collects a month, once per payment', function (): void {
    $this->seller->update(['premium_subscription_id' => 'I-SUB1', 'premium_subscription_status' => 'active']);
    $next = now()->addMonth()->startOfSecond();

    fakePayPal(['*/v1/billing/subscriptions/I-SUB1' => Http::response([
        'id' => 'I-SUB1',
        'status' => 'ACTIVE',
        'billing_info' => ['next_billing_time' => $next->toIso8601String()],
    ])]);

    $event = [
        'id' => 'WH-SALE-1',
        'event_type' => 'PAYMENT.SALE.COMPLETED',
        'resource' => ['id' => 'SALE-1', 'billing_agreement_id' => 'I-SUB1', 'amount' => ['total' => '29.00', 'currency' => 'USD']],
    ];

    paypalWebhook($event)->assertNoContent();
    paypalWebhook([...$event, 'id' => 'WH-SALE-1-RETRY'])->assertNoContent();

    expect($this->seller->fresh()->premium_until->toDateString())->toBe($next->addDays(3)->toDateString())
        ->and(Payment::query()->where('purpose', Payment::PURPOSE_PREMIUM)->count())->toBe(1);
});

it('lets PayPal post without a CSRF token', function (): void {
    // Tests skip CSRF entirely, so a request here would pass either way;
    // the route's resolved middleware is what PayPal actually meets.
    $route = Route::getRoutes()->getByName('webhooks.paypal');

    expect(app('router')->gatherRouteMiddleware($route))->not->toContain(PreventRequestForgery::class);
});
