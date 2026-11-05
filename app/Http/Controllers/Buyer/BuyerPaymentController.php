<?php

namespace App\Http\Controllers\Buyer;

use App\Actions\Payments\CompleteCheckout;
use App\Actions\Payments\StartCheckout;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Payments\PaymentFailed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paying for an order through PayPal.
 *
 * The buyer is sent to PayPal and comes back to `returned`, which takes
 * the money. Only a PayPal order this order already started is
 * captured, so a return link carrying someone else's token gets a 404
 * rather than charging or crediting the wrong sale.
 */
class BuyerPaymentController extends Controller
{
    public function store(Request $request, Order $order, StartCheckout $checkout): RedirectResponse
    {
        $this->authoriseBuyer($request, $order);

        try {
            return redirect()->away($checkout->handle($order));
        } catch (PaymentFailed $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function returned(Request $request, Order $order, CompleteCheckout $checkout): RedirectResponse
    {
        $this->authoriseBuyer($request, $order);

        $token = $request->query('token');

        abort_unless(
            is_string($token) && $order->payments()->where('provider_order_id', $token)->exists(),
            Response::HTTP_NOT_FOUND,
        );

        try {
            $payment = $checkout->handle($token);
        } catch (PaymentFailed $e) {
            return redirect()->route('buyer.orders.show', $order)->with('error', $e->getMessage());
        }

        return redirect()->route('buyer.orders.show', $order)->with('status', match ($payment->status) {
            Payment::STATUS_PENDING => __('PayPal is still confirming your payment. This order updates the moment it clears.'),
            default => __('Payment received. The seller has been told to post your stone.'),
        });
    }

    /** Backed out in PayPal. Nothing was charged and the order waits. */
    public function cancelled(Request $request, Order $order): RedirectResponse
    {
        $this->authoriseBuyer($request, $order);

        $token = $request->query('token');

        if (is_string($token)) {
            $order->payments()
                ->where('provider_order_id', $token)
                ->where('status', Payment::STATUS_CREATED)
                ->update(['status' => Payment::STATUS_FAILED, 'failure_reason' => __('Cancelled in PayPal.')]);
        }

        return redirect()->route('buyer.orders.show', $order)
            ->with('info', __('Payment cancelled — nothing was charged. Your order is here when you are ready.'));
    }

    private function authoriseBuyer(Request $request, Order $order): void
    {
        abort_unless(
            (int) $order->buyer_id === (int) $request->user('buyer')->id,
            Response::HTTP_FORBIDDEN,
        );
    }
}
