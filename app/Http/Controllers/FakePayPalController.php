<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Money;
use App\Support\Payments\FakePaymentGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * The local stand-in for PayPal's approval page (PAYMENTS_DRIVER=fake).
 *
 * 404 unless the simulator is the active driver, so it cannot exist
 * beside real payments. Where each choice leads was stored when the
 * session opened; nothing posted here can redirect anywhere else.
 */
class FakePayPalController extends Controller
{
    public function show(string $session, PaymentGateway $gateway): View
    {
        $data = $this->simulator($gateway)->session($session);

        abort_if($data === null, Response::HTTP_NOT_FOUND);

        return view('pages.payments.simulator', [
            'id' => $session,
            'session' => $data,
            'amount' => isset($data['amount_cents'])
                ? Money::fromCents((int) $data['amount_cents'], (string) ($data['currency'] ?? 'USD'))
                : null,
            'fee' => ($data['fee_cents'] ?? 0) > 0
                ? Money::fromCents((int) $data['fee_cents'], (string) ($data['currency'] ?? 'USD'))
                : null,
        ]);
    }

    public function decide(Request $request, string $session, PaymentGateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'decline', 'cancel'])],
        ]);

        $next = $this->simulator($gateway)->decide($session, $validated['decision']);

        abort_if($next === null, Response::HTTP_NOT_FOUND);

        return redirect()->to($next);
    }

    private function simulator(PaymentGateway $gateway): FakePaymentGateway
    {
        abort_unless($gateway instanceof FakePaymentGateway, Response::HTTP_NOT_FOUND);

        return $gateway;
    }
}
