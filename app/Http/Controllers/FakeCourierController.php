<?php

namespace App\Http\Controllers;

use App\Actions\Shipping\TrackShipment;
use App\Contracts\ParcelTracker;
use App\Models\Order;
use App\Support\ShipmentStatuses;
use App\Support\Tracking\CourierScan;
use App\Support\Tracking\FakeParcelTracker;
use App\Support\Tracking\ParcelReport;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * The local courier (TRACKING_DRIVER=fake): press a button, and the
 * parcel is scanned as a real carrier would scan it.
 *
 * The scan goes to TrackShipment::courier(), exactly where a verified
 * webhook goes, so notifications, the order's status and delivery all
 * behave as they will in production. 404 unless the simulator is the
 * driver; the links are signed, so an order reference alone opens nothing.
 */
class FakeCourierController extends Controller
{
    /** What a carrier's scan says at each step. */
    private const SCANS = [
        'info_received' => 'Shipment information received',
        'in_transit' => 'Departed sorting facility',
        'customs' => 'Held by customs for inspection',
        'out_for_delivery' => 'With the delivery courier',
        'available_for_pickup' => 'Arrived at the collection point',
        'delivery_failed' => 'Recipient not available — card left',
        'exception' => 'Shipment damaged in transit',
        'returned' => 'Returning to sender',
        'delivered' => 'Delivered — signed for',
    ];

    public function show(Order $order, ParcelTracker $tracker): View
    {
        $this->simulator($tracker, $order);

        return view('pages.tracking.simulator', [
            'order' => $order->load('shipmentEvents'),
            'scans' => collect(self::SCANS)->mapWithKeys(fn (string $text, string $status): array => [
                $status => ShipmentStatuses::label($status),
            ])->all(),
            'action' => URL::signedRoute('tracking.simulator.scan', $order),
        ]);
    }

    public function scan(Request $request, Order $order, ParcelTracker $tracker, TrackShipment $shipments): RedirectResponse
    {
        $this->simulator($tracker, $order);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::SCANS))],
            'location' => ['nullable', 'string', 'max:'.config('verum_tracking.limits.location')],
        ]);

        $added = $shipments->courier(new ParcelReport((string) $order->tracking_number, [
            new CourierScan(
                $validated['status'],
                self::SCANS[$validated['status']],
                $validated['location'] ?? null,
                CarbonImmutable::now(),
            ),
        ]));

        return redirect()->to(URL::signedRoute('tracking.simulator', $order))->with(
            $added > 0 ? 'status' : 'info',
            $added > 0
                ? __('Scanned: :status.', ['status' => ShipmentStatuses::label($validated['status'])])
                : __('That scan was already recorded.'),
        );
    }

    private function simulator(ParcelTracker $tracker, Order $order): void
    {
        abort_unless($tracker instanceof FakeParcelTracker, Response::HTTP_NOT_FOUND);
        abort_if(blank($order->tracking_number), Response::HTTP_NOT_FOUND);
    }
}
