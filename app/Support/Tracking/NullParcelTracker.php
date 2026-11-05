<?php

namespace App\Support\Tracking;

use App\Contracts\ParcelTracker;
use Illuminate\Http\Request;

/**
 * No courier API (TRACKING_DRIVER=off): the buyer still gets the
 * carrier's own tracking link and the seller's updates.
 */
class NullParcelTracker implements ParcelTracker
{
    public function watches(): bool
    {
        return false;
    }

    public function register(string $number, ?int $carrier): bool
    {
        return false;
    }

    public function fetch(array $parcels): array
    {
        return [];
    }

    public function verifyWebhook(Request $request): bool
    {
        return false;
    }

    public function parseWebhook(array $payload): array
    {
        return [];
    }
}
