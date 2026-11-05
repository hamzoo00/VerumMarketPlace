<?php

namespace App\Support\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Seller;
use App\Support\Carriers;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PayPal's REST API, in sandbox or live by PAYPAL_MODE.
 *
 * Marketplace shape ("Multiparty"): the buyer pays the seller's own
 * account (`payee`) and PayPal moves the commission to the platform
 * (`platform_fees`), so the platform never holds a buyer's money and
 * buyer protection is PayPal's.
 *
 * Talks to the API with Laravel's HTTP client rather than an SDK: the
 * handful of calls below is the whole integration, and Http::fake()
 * stands in for PayPal in every test. Anything other than an exact
 * `live` mode is the sandbox, so a typo can never charge real money.
 */
final class PayPalGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'paypal';
    }

    public function onboardingUrl(string $token, string $returnUrl): string
    {
        $response = $this->send('post', '/v2/customer/partner-referrals', [
            'tracking_id' => $token,
            'operations' => [[
                'operation' => 'API_INTEGRATION',
                'api_integration_preference' => [
                    'rest_api_integration' => [
                        'integration_method' => 'PAYPAL',
                        'integration_type' => 'THIRD_PARTY',
                        'third_party_details' => [
                            'features' => ['PAYMENT', 'REFUND', 'PARTNER_FEE', 'ACCESS_MERCHANT_INFORMATION', 'TRACKING_SHIPMENT_READWRITE'],
                        ],
                    ],
                ],
            ]],
            'products' => ['EXPRESS_CHECKOUT'],
            'legal_consents' => [['type' => 'SHARE_DATA_CONSENT', 'granted' => true]],
            'partner_config_override' => ['return_url' => $returnUrl],
        ], __('PayPal could not start connecting your account. Please try again.'));

        return $this->link($response, 'action_url')
            ?? throw new PaymentFailed(__('PayPal could not start connecting your account. Please try again.'));
    }

    public function merchantCanReceive(string $merchantId): bool
    {
        $partner = rawurlencode((string) config('services.paypal.partner_merchant_id'));

        $response = $this->send(
            'get',
            "/v1/customer/partners/{$partner}/merchant-integrations/".rawurlencode($merchantId),
            [],
            __('PayPal could not confirm your account just now. Please try again.'),
        );

        return $response->json('payments_receivable') === true
            && $response->json('primary_email_confirmed') === true;
    }

    public function createCheckout(Payment $payment, Order $order, string $returnUrl, string $cancelUrl): GatewayRedirect
    {
        $unit = [
            'reference_id' => $order->reference,
            // PayPal refuses a repeated invoice id, and a buyer may try twice.
            'invoice_id' => $order->reference.'-'.$payment->id,
            'custom_id' => $payment->uuid,
            'description' => Str::limit(__('Order :reference on :app', [
                'reference' => $order->reference,
                'app' => config('app.name'),
            ]), 120, ''),
            'amount' => $this->amount($order),
            'payee' => ['merchant_id' => (string) $order->seller?->paypal_merchant_id],
        ];

        if ((int) $payment->fee_cents > 0) {
            $unit['payment_instruction'] = [
                'disbursement_mode' => 'INSTANT',
                'platform_fees' => [['amount' => $this->money((int) $payment->fee_cents, $payment->currency)]],
            ];
        }

        // Ship where the order says. PayPal's seller protection covers a
        // parcel sent to the address on the payment, so it must match.
        if ($order->hasAddress()) {
            $unit['shipping'] = [
                'name' => ['full_name' => Str::limit((string) $order->ship_to_name, 300, '')],
                'address' => array_filter([
                    'address_line_1' => $order->ship_to_line1,
                    'address_line_2' => $order->ship_to_line2,
                    'admin_area_2' => $order->ship_to_city,
                    'admin_area_1' => $order->ship_to_region,
                    'postal_code' => $order->ship_to_postcode,
                    'country_code' => $order->ship_to_country,
                ]),
            ];
        }

        $response = $this->send('post', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [$unit],
            'payment_source' => ['paypal' => ['experience_context' => [
                'brand_name' => Str::limit((string) config('app.name'), 120, ''),
                'user_action' => 'PAY_NOW',
                // An auction win has no address yet: the buyer picks one in
                // PayPal and CompleteCheckout copies it onto the order.
                'shipping_preference' => $order->hasAddress() ? 'SET_PROVIDED_ADDRESS' : 'GET_FROM_FILE',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ]]],
        ], __('PayPal could not start this payment. Please try again in a moment.'), [
            'PayPal-Request-Id' => 'checkout-'.$payment->uuid,
        ]);

        $id = $response->json('id');
        $url = $this->link($response, 'payer-action') ?? $this->link($response, 'approve');

        if (! is_string($id) || $url === null) {
            throw new PaymentFailed(__('PayPal could not start this payment. Please try again in a moment.'));
        }

        return new GatewayRedirect($id, $url);
    }

    public function capture(string $providerOrderId): CapturedPayment
    {
        $path = '/v2/checkout/orders/'.rawurlencode($providerOrderId);

        try {
            // No request id here on purpose: a capture that was pending
            // must be asked again for its real status, not replayed.
            $response = $this->request()
                ->withHeaders(['Prefer' => 'return=representation'])
                ->withBody('{}', 'application/json')
                ->post($path.'/capture');

            if ($this->issue($response) === 'ORDER_ALREADY_CAPTURED') {
                $response = $this->request()->get($path);
            }
        } catch (ConnectionException $e) {
            throw $this->unreachable($e, __('PayPal did not answer. You have not been charged twice — please try again.'));
        }

        throw_if($this->issue($response) === 'INSTRUMENT_DECLINED', new PaymentFailed(
            __('PayPal declined that payment method. Please try again and choose another one.'),
        ));

        throw_if($this->issue($response) === 'ORDER_NOT_APPROVED', new PaymentFailed(
            __('The payment was not approved in PayPal. Please try again.'),
        ));

        $this->ensureOk($response, __('PayPal could not take this payment. You have not been charged; please try again.'));

        return $this->captured($response);
    }

    public function refund(Payment $payment, ?int $amountCents = null): RefundedPayment
    {
        $full = $amountCents === null || $amountCents >= (int) $payment->amount_cents;
        $body = $full ? [] : ['amount' => $this->money($amountCents, $payment->currency)];

        // A full refund gives the commission back too: the seller should
        // not pay a fee on a sale that did not happen.
        if ($full && (int) $payment->fee_cents > 0) {
            $body['payment_instruction'] = [
                'platform_fees' => [['amount' => $this->money((int) $payment->fee_cents, $payment->currency)]],
            ];
        }

        $response = $this->send(
            'post',
            '/v2/payments/captures/'.rawurlencode((string) $payment->provider_capture_id).'/refund',
            $body,
            __('PayPal could not refund this payment. You can refund the buyer from your PayPal account instead.'),
            [
                // The capture belongs to the seller's account; this says we act for them.
                'PayPal-Auth-Assertion' => $this->authAssertion((string) $payment->seller?->paypal_merchant_id),
                'PayPal-Request-Id' => 'refund-'.$payment->uuid.'-'.($full ? 'full' : $amountCents),
            ],
        );

        return new RefundedPayment(
            (string) $response->json('id'),
            $this->cents($response->json('amount.value')) ?: ($full ? (int) $payment->amount_cents : (int) $amountCents),
        );
    }

    public function createSubscription(Seller $seller, string $requestId, string $returnUrl, string $cancelUrl): GatewayRedirect
    {
        $plan = (string) config('services.paypal.premium_plan_id');

        if ($plan === '') {
            Log::error('PayPal premium plan is not configured: run `php artisan paypal:premium-plan`.');

            throw new PaymentFailed(__('Premium is not available just yet. Please try again later.'));
        }

        $response = $this->send('post', '/v1/billing/subscriptions', [
            'plan_id' => $plan,
            'custom_id' => (string) $seller->id,
            'application_context' => [
                'brand_name' => Str::limit((string) config('app.name'), 120, ''),
                'user_action' => 'SUBSCRIBE_NOW',
                'shipping_preference' => 'NO_SHIPPING',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ], __('PayPal could not start the subscription. Please try again.'), [
            'PayPal-Request-Id' => 'premium-'.$requestId,
        ]);

        $id = $response->json('id');
        $url = $this->link($response, 'approve');

        if (! is_string($id) || $url === null) {
            throw new PaymentFailed(__('PayPal could not start the subscription. Please try again.'));
        }

        return new GatewayRedirect($id, $url);
    }

    public function subscription(string $subscriptionId): SubscriptionState
    {
        $response = $this->send(
            'get',
            '/v1/billing/subscriptions/'.rawurlencode($subscriptionId),
            [],
            __('PayPal could not confirm the subscription just now. Please try again.'),
        );

        $next = $response->json('billing_info.next_billing_time');

        return new SubscriptionState(
            $subscriptionId,
            Str::lower((string) $response->json('status')),
            is_string($next) ? CarbonImmutable::parse($next) : null,
        );
    }

    public function cancelSubscription(string $subscriptionId, string $reason): void
    {
        try {
            $response = $this->request()->post(
                '/v1/billing/subscriptions/'.rawurlencode($subscriptionId).'/cancel',
                ['reason' => Str::limit($reason, 120, '')],
            );
        } catch (ConnectionException $e) {
            throw $this->unreachable($e, __('PayPal did not answer. Please try again.'));
        }

        // Already cancelled or expired is the outcome asked for.
        if ($this->issue($response) === 'SUBSCRIPTION_STATUS_INVALID') {
            return;
        }

        $this->ensureOk($response, __('PayPal could not cancel the subscription. Please try again.'));
    }

    public function addTracking(Payment $payment, string $carrierKey, string $trackingNumber): void
    {
        $carrier = Carriers::paypalCode($carrierKey);

        $this->send(
            'post',
            '/v2/checkout/orders/'.rawurlencode((string) $payment->provider_order_id).'/track',
            array_filter([
                'capture_id' => $payment->provider_capture_id,
                'tracking_number' => $trackingNumber,
                'carrier' => $carrier,
                'carrier_name_other' => $carrier === 'OTHER' ? Carriers::name($carrierKey) : null,
                'notify_payer' => false,
            ], fn (mixed $value): bool => $value !== null),
            __('PayPal could not take the tracking number.'),
        );
    }

    public function verifyWebhook(Request $request): ?array
    {
        $webhookId = (string) config('services.paypal.webhook_id');

        if ($webhookId === '') {
            Log::error('PayPal webhook received but PAYPAL_WEBHOOK_ID is not set.');

            return null;
        }

        $signature = [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => $webhookId,
        ];

        $raw = $request->getContent();
        $event = json_decode($raw, true);

        if (in_array(null, $signature, true) || ! is_array($event)) {
            return null;
        }

        // The event goes back byte for byte: re-encoding it can reorder or
        // reformat a field, and the signature check would then fail.
        $body = substr((string) json_encode($signature), 0, -1).',"webhook_event":'.$raw.'}';

        try {
            $response = $this->request()
                ->withBody($body, 'application/json')
                ->post('/v1/notifications/verify-webhook-signature');
        } catch (ConnectionException|PaymentFailed $e) {
            Log::warning('PayPal webhook could not be verified.', ['error' => $e->getMessage()]);

            return null;
        }

        if ($response->json('verification_status') !== 'SUCCESS') {
            Log::warning('PayPal webhook failed verification.', ['transmission_id' => $signature['transmission_id']]);

            return null;
        }

        /** @var array<string, mixed> $event */
        return $event;
    }

    /**
     * Create the monthly premium plan, once per PayPal account and price.
     *
     * Only `paypal:premium-plan` calls this; the plan id it returns goes
     * into PAYPAL_PREMIUM_PLAN_ID.
     */
    public function createPremiumPlan(int $priceCents, string $name): string
    {
        $currency = (string) config('verum_payments.currency', 'USD');

        $product = $this->send('post', '/v1/catalogs/products', [
            'name' => $name,
            'type' => 'SERVICE',
        ], __('PayPal could not create the premium product.'));

        $plan = $this->send('post', '/v1/billing/plans', [
            'product_id' => $product->json('id'),
            'name' => $name,
            'status' => 'ACTIVE',
            'billing_cycles' => [[
                'frequency' => ['interval_unit' => 'MONTH', 'interval_count' => 1],
                'tenure_type' => 'REGULAR',
                'sequence' => 1,
                'total_cycles' => 0,
                'pricing_scheme' => ['fixed_price' => $this->money($priceCents, $currency)],
            ]],
            'payment_preferences' => [
                'auto_bill_outstanding' => true,
                'payment_failure_threshold' => 2,
            ],
        ], __('PayPal could not create the premium plan.'));

        return (string) $plan->json('id');
    }

    /** sandbox unless the mode is exactly `live`. */
    public function mode(): string
    {
        return config('services.paypal.mode') === 'live' ? 'live' : 'sandbox';
    }

    private function host(): string
    {
        return (string) config('verum_payments.hosts.'.$this->mode());
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl($this->host())
            ->withToken($this->token())
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(25);

        $bn = (string) config('services.paypal.bn_code');

        return $bn === '' ? $request : $request->withHeaders(['PayPal-Partner-Attribution-Id' => $bn]);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    private function send(string $method, string $path, array $body, string $failure, array $headers = []): Response
    {
        try {
            $request = $this->request()->withHeaders($headers);

            $response = $method === 'get'
                ? $request->get($path)
                : $request->withBody($body === [] ? '{}' : (string) json_encode($body), 'application/json')->post($path);
        } catch (ConnectionException $e) {
            throw $this->unreachable($e, $failure);
        }

        $this->ensureOk($response, $failure);

        return $response;
    }

    /** An access token, kept until shortly before PayPal expires it. */
    private function token(): string
    {
        $id = (string) config('services.paypal.client_id');
        $secret = (string) config('services.paypal.client_secret');

        if ($id === '' || $secret === '') {
            Log::error('PayPal is the payments driver but PAYPAL_CLIENT_ID / PAYPAL_CLIENT_SECRET are not set.');

            throw new PaymentFailed(__('Payments are not available just now. Please try again later.'));
        }

        // Keyed by mode and client, so a sandbox token is never sent live.
        $key = 'verum:paypal:token:'.$this->mode().':'.hash('sha256', $id);
        $cached = Cache::get($key);

        if (is_string($cached)) {
            return $cached;
        }

        try {
            $response = Http::baseUrl($this->host())
                ->withBasicAuth($id, $secret)
                ->asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post('/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        } catch (ConnectionException $e) {
            throw $this->unreachable($e, __('Payments are not available just now. Please try again later.'));
        }

        $this->ensureOk($response, __('Payments are not available just now. Please try again later.'));

        $token = (string) $response->json('access_token');
        $lifetime = (int) $response->json('expires_in', 3600);

        Cache::put($key, $token, max(60, $lifetime - 300));

        return $token;
    }

    private function captured(Response $response): CapturedPayment
    {
        $capture = 'purchase_units.0.payments.captures.0.';
        $shipping = 'purchase_units.0.shipping.';

        $status = match (Str::upper((string) $response->json($capture.'status'))) {
            'COMPLETED' => CapturedPayment::COMPLETED,
            'PENDING' => CapturedPayment::PENDING,
            default => CapturedPayment::FAILED,
        };

        $line1 = $response->json($shipping.'address.address_line_1');

        return new CapturedPayment(
            status: $status,
            captureId: is_string($id = $response->json($capture.'id')) ? $id : null,
            amountCents: $this->cents($response->json($capture.'amount.value')),
            feeCents: $this->cents($response->json($capture.'seller_receivable_breakdown.platform_fees.0.amount.value')),
            address: is_string($line1) ? [
                'name' => $this->text($response->json($shipping.'name.full_name')),
                'line1' => $line1,
                'line2' => $this->text($response->json($shipping.'address.address_line_2')),
                'city' => $this->text($response->json($shipping.'address.admin_area_2')),
                'region' => $this->text($response->json($shipping.'address.admin_area_1')),
                'postcode' => $this->text($response->json($shipping.'address.postal_code')),
                'country' => $this->text($response->json($shipping.'address.country_code')),
            ] : null,
        );
    }

    /**
     * The total, and its parts when they add up — PayPal rejects a
     * breakdown that does not.
     *
     * @return array<string, mixed>
     */
    private function amount(Order $order): array
    {
        $currency = $order->currency;
        $items = (int) $order->subtotal_cents;
        $shipping = (int) $order->shipping_cents;
        $discount = (int) $order->discount_cents;
        $total = (int) $order->total_cents;

        $amount = $this->money($total, $currency);

        if ($items + $shipping - $discount === $total) {
            $amount['breakdown'] = array_filter([
                'item_total' => $this->money($items, $currency),
                'shipping' => $shipping > 0 ? $this->money($shipping, $currency) : null,
                'discount' => $discount > 0 ? $this->money($discount, $currency) : null,
            ]);
        }

        return $amount;
    }

    /** @return array{currency_code: string, value: string} */
    private function money(int $cents, string $currency): array
    {
        return [
            'currency_code' => $currency,
            'value' => sprintf('%d.%02d', intdiv($cents, 100), $cents % 100),
        ];
    }

    private function cents(mixed $value): int
    {
        return is_numeric($value) ? (int) round(((float) $value) * 100) : 0;
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function link(Response $response, string $rel): ?string
    {
        foreach ((array) $response->json('links', []) as $link) {
            if (is_array($link) && ($link['rel'] ?? null) === $rel && is_string($link['href'] ?? null)) {
                return $link['href'];
            }
        }

        return null;
    }

    private function issue(Response $response): ?string
    {
        $issue = $response->json('details.0.issue');

        return is_string($issue) ? $issue : null;
    }

    private function ensureOk(Response $response, string $failure): void
    {
        if ($response->successful()) {
            return;
        }

        // PayPal's debug id is what their support asks for.
        Log::warning('PayPal request failed.', [
            'status' => $response->status(),
            'name' => $response->json('name'),
            'issue' => $this->issue($response),
            'debug_id' => $response->json('debug_id'),
        ]);

        throw new PaymentFailed($failure);
    }

    private function unreachable(ConnectionException $e, string $failure): PaymentFailed
    {
        Log::warning('PayPal could not be reached.', ['error' => $e->getMessage()]);

        return new PaymentFailed($failure, previous: $e);
    }

    /** Says the platform acts for this merchant, as PayPal requires. */
    private function authAssertion(string $merchantId): string
    {
        $encode = fn (array $part): string => rtrim(strtr(base64_encode((string) json_encode($part)), '+/', '-_'), '=');

        return $encode(['alg' => 'none']).'.'.$encode([
            'iss' => (string) config('services.paypal.client_id'),
            'payer_id' => $merchantId,
        ]).'.';
    }
}
