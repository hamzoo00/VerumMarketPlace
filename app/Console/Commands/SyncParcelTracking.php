<?php

namespace App\Console\Commands;

use App\Actions\Shipping\TrackShipment;
use App\Contracts\ParcelTracker;
use App\Jobs\RegisterParcel;
use App\Models\Order;
use App\Support\Carriers;
use App\Support\Tracking\TrackingFailed;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Sleep;

/**
 * Asks the tracking API about every parcel still on the road.
 *
 * The webhook is the fast path; this is the safety net. It catches a
 * push that never arrived, a number whose registration failed for good,
 * and every update on a machine the provider cannot reach. Each order is
 * asked at most every `every_hours`, oldest answer first, and a parcel
 * posted more than `give_up_after_days` ago is left alone.
 */
class SyncParcelTracking extends Command
{
    protected $signature = 'tracking:sync';

    protected $description = 'Fetch courier scans for parcels on their way';

    public function handle(ParcelTracker $tracker, TrackShipment $shipments): int
    {
        if (! $tracker->watches()) {
            $this->components->info('No tracking API is configured (TRACKING_DRIVER). Nothing to do.');

            return self::SUCCESS;
        }

        $settings = (array) config('verum_tracking.sync');
        $limit = (int) $settings['max_per_run'];

        $registered = $this->registerMissing($limit);
        [$asked, $added, $failed] = $this->fetchDue($tracker, $shipments, $settings);

        $this->components->info("Registered {$registered}, checked {$asked}, {$added} new scans.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Numbers never given to the API: its registration job gave up, or
     * the parcel was posted before a driver was configured. A number the
     * API refused carries `tracking_synced_at` and is not asked again.
     */
    private function registerMissing(int $limit): int
    {
        return $this->onTheRoad()
            ->whereNull('tracking_registered_at')
            ->whereNull('tracking_synced_at')
            ->limit($limit)
            ->get(['id', 'tracking_number'])
            ->each(fn (Order $order) => RegisterParcel::dispatch($order->id, (string) $order->tracking_number))
            ->count();
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{0: int, 1: int, 2: bool}
     */
    private function fetchDue(ParcelTracker $tracker, TrackShipment $shipments, array $settings): array
    {
        $due = $this->onTheRoad()
            ->whereNotNull('tracking_registered_at')
            ->where(fn (Builder $query) => $query
                ->whereNull('tracking_synced_at')
                ->orWhere('tracking_synced_at', '<=', now()->subHours((int) $settings['every_hours'])))
            ->orderByRaw('tracking_synced_at asc nulls first')
            ->limit((int) $settings['max_per_run'])
            ->get(['id', 'tracking_carrier', 'tracking_number']);

        $added = 0;

        foreach ($due->chunk((int) $settings['batch'])->values() as $index => $chunk) {
            // The provider allows three requests a second.
            if ($index > 0) {
                Sleep::usleep(400_000);
            }

            try {
                $reports = $tracker->fetch($this->parcels($chunk));
            } catch (TrackingFailed $e) {
                $this->components->error($e->getMessage().' The rest wait for the next run.');

                return [$due->count(), $added, true];
            }

            foreach ($reports as $report) {
                $added += $shipments->courier($report);
            }

            Order::query()->whereKey($chunk->modelKeys())->update(['tracking_synced_at' => now()]);
        }

        return [$due->count(), $added, false];
    }

    /**
     * One entry per number: two orders posted in one parcel share it.
     *
     * @param  Collection<int, Order>  $orders
     * @return list<array{number: string, carrier: int|null}>
     */
    private function parcels(Collection $orders): array
    {
        return array_values($orders
            ->unique('tracking_number')
            ->map(fn (Order $order): array => [
                'number' => (string) $order->tracking_number,
                'carrier' => Carriers::trackerCode($order->tracking_carrier),
            ])
            ->all());
    }

    /** @return Builder<Order> */
    private function onTheRoad(): Builder
    {
        return Order::query()
            ->where('status', Order::STATUS_DISPATCHED)
            ->whereNotNull('tracking_number')
            ->where('dispatched_at', '>=', now()->subDays((int) config('verum_tracking.sync.give_up_after_days')));
    }
}
