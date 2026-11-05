<?php

namespace App\Http\Controllers;

use App\Actions\Shipping\TrackShipment;
use App\Contracts\ParcelTracker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Where the tracking API pushes a parcel's new scans.
 *
 * Nothing is believed until the driver has checked the signature on the
 * raw body, so a forged POST changes nothing. A repeat is harmless:
 * TrackShipment skips a scan it has already recorded. Answered 200 once
 * handled; an error answers 5xx and the provider retries.
 */
class TrackingWebhookController extends Controller
{
    public function __invoke(Request $request, ParcelTracker $tracker, TrackShipment $shipments): Response
    {
        if (! $tracker->verifyWebhook($request)) {
            return response('', Response::HTTP_BAD_REQUEST);
        }

        $payload = json_decode($request->getContent(), true);

        foreach ($tracker->parseWebhook(is_array($payload) ? $payload : []) as $report) {
            $shipments->courier($report);
        }

        return response('', Response::HTTP_OK);
    }
}
