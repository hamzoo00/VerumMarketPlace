<?php

namespace App\Contracts;

use App\Support\Tracking\ParcelReport;
use App\Support\Tracking\TrackingFailed;
use Illuminate\Http\Request;

/**
 * A courier-tracking API: told which numbers to watch, it reports scans.
 *
 * TRACKING_DRIVER picks the implementation (config/verum_tracking.php).
 * Reports come back in this app's own vocabulary — a driver translates
 * the provider's statuses — so nothing past the driver knows which
 * provider answered.
 */
interface ParcelTracker
{
    /** Whether anything is watching numbers at all; false is links-only. */
    public function watches(): bool;

    /**
     * Start watching a number. Registering one twice is not an error.
     *
     * False when the provider refused the number itself (unreadable, or
     * no carrier recognised) — asking again will not change that. A
     * provider that is down throws instead, which is worth a retry.
     *
     * @throws TrackingFailed
     */
    public function register(string $number, ?int $carrier): bool;

    /**
     * Everything known about these parcels now.
     *
     * @param  list<array{number: string, carrier: int|null}>  $parcels
     * @return list<ParcelReport>
     *
     * @throws TrackingFailed
     */
    public function fetch(array $parcels): array;

    /** Did this push really come from the provider? Checked on the raw body. */
    public function verifyWebhook(Request $request): bool;

    /**
     * The reports a verified push carries.
     *
     * @param  array<string, mixed>  $payload
     * @return list<ParcelReport>
     */
    public function parseWebhook(array $payload): array;
}
