<?php

namespace App\Http\Controllers;

use App\Actions\Payments\HandlePaymentEvent;
use App\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Where PayPal reports what happened after the buyer left: a capture
 * clearing, a refund, a dispute, a subscription renewing.
 *
 * Nothing is believed until PayPal itself confirms the signature
 * (PaymentGateway::verifyWebhook), so a forged POST here changes nothing.
 * A verified event is acknowledged with 2xx once handled; an error
 * answers 5xx and PayPal retries, which HandlePaymentEvent makes safe.
 */
class PayPalWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, HandlePaymentEvent $events): Response
    {
        $event = $gateway->verifyWebhook($request);

        if ($event === null) {
            return response('', Response::HTTP_BAD_REQUEST);
        }

        $events->handle($event, $gateway->name());

        return response()->noContent();
    }
}
