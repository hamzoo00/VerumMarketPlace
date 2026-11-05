<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Shipping\TrackShipment;
use App\Http\Controllers\Concerns\RespondsToActions;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Carriers;
use App\Support\ShipmentStatuses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The seller's side of a parcel's journey: where it has got to, and a
 * mistyped tracking number put right.
 *
 * Both go through TrackShipment, which writes the step and tells the
 * buyer. Outside the trading gate, like the rest of the order pages: a
 * suspended store still has parcels on the road.
 */
class SellerShipmentController extends Controller
{
    use RespondsToActions;

    public function __construct(private readonly TrackShipment $shipments) {}

    public function store(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $this->authoriseSeller($request, $order);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ShipmentStatuses::pickableFor($order)))],
            'description' => ['nullable', 'string', 'max:'.config('verum_tracking.limits.description')],
            'location' => ['nullable', 'string', 'max:'.config('verum_tracking.limits.location')],
        ], [
            'status.in' => __('Choose what has happened to the parcel.'),
        ]);

        try {
            $this->shipments->sellerUpdate(
                $order,
                $request->user('seller'),
                $validated['status'],
                $validated['description'] ?? null,
                $validated['location'] ?? null,
            );
        } catch (RuntimeException $e) {
            return $this->refused($request, $e->getMessage());
        }

        return $this->done($request, __('Update recorded. The buyer can see it now.'));
    }

    public function update(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $this->authoriseSeller($request, $order);

        $validated = $request->validate([
            'tracking_carrier' => ['required', Rule::in(array_keys(Carriers::options()))],
            'tracking_number' => ['required', 'string', 'regex:~^'.Carriers::trackingPattern().'$~'],
        ], [
            'tracking_number.regex' => __('A tracking number is letters, digits and dashes, as printed on the receipt.'),
        ]);

        try {
            $this->shipments->retrack($order, $request->user('seller'), $validated['tracking_carrier'], $validated['tracking_number']);
        } catch (RuntimeException $e) {
            return $this->refused($request, $e->getMessage());
        }

        return $this->done($request, __('Tracking number corrected. The buyer has the new link.'));
    }

    /** A seller reaches only their own sales; another gets 403. */
    private function authoriseSeller(Request $request, Order $order): void
    {
        abort_unless(
            (int) $order->seller_id === (int) ($request->user('seller')->storefront?->id),
            Response::HTTP_FORBIDDEN,
        );
    }
}
