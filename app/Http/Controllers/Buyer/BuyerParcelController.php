<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Track parcel" from the bell: on to the carrier's own tracking page.
 *
 * The bell only follows links on this site, so a courier scan's
 * notification points here and this sends the buyer on. The destination
 * is built from config (Carriers::trackingUrl) with the number encoded,
 * never read from the request, so this cannot become an open redirect.
 */
class BuyerParcelController extends Controller
{
    public function __invoke(Request $request, Order $order): RedirectResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user('buyer')->id, Response::HTTP_FORBIDDEN);

        $url = $order->trackingUrl();

        if ($url === null) {
            return redirect()->route('buyer.orders.show', $order)
                ->with('info', __('There is no tracking number on this order yet.'));
        }

        return redirect()->away($url);
    }
}
