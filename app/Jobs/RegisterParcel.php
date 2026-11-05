<?php

namespace App\Jobs;

use App\Contracts\ParcelTracker;
use App\Models\Order;
use App\Support\Carriers;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Asks the tracking API to start watching a parcel's number.
 *
 * Queued, like PushTrackingToPayPal: marking an order dispatched must not
 * wait on a courier API or fail because one is slow. A provider that is
 * down throws and the queue retries; one that refuses the number (no
 * carrier recognised) is answered by stamping `tracking_synced_at`, so
 * the poll does not keep asking — the buyer still has the carrier's link
 * and the seller's updates.
 */
class RegisterParcel implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public int $orderId, public string $number)
    {
        $this->afterCommit();
    }

    public function handle(ParcelTracker $tracker): void
    {
        $order = Order::find($this->orderId);

        // The seller may have corrected the number since this was queued.
        if ($order === null || $order->tracking_number !== $this->number || ! $tracker->watches()) {
            return;
        }

        $watched = $tracker->register($this->number, Carriers::trackerCode($order->tracking_carrier));

        $order->update($watched
            ? ['tracking_registered_at' => now()]
            : ['tracking_synced_at' => now()]);
    }
}
