<?php

namespace App\Support\Tracking;

/**
 * The local courier (TRACKING_DRIVER=fake).
 *
 * Accepts every number and reports nothing on its own: scans come from
 * the courier simulator page, which hands them to the same
 * TrackShipment::courier() a real webhook reaches. Refused in production
 * by the container binding — a seller could otherwise "deliver" their
 * own parcel under a courier's name.
 */
final class FakeParcelTracker extends NullParcelTracker
{
    public function watches(): bool
    {
        return true;
    }

    public function register(string $number, ?int $carrier): bool
    {
        return true;
    }
}
