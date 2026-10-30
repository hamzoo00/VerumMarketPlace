<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Seller;
use App\Models\ShippingRate;
use App\Models\SupportCase;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * How long a closing seller has to stay open.
 *
 * A store that closes still owes the buyers whose parcels are on the way:
 * they must be able to arrive, and the buyer must be able to dispute them.
 * So the account stays open until the latest of those, per order:
 *
 *   dispatched → dispatch date + longest transit for its service + window
 *   delivered  → delivery date + window
 *
 * Pure reads, no writes — DecideSellerStanding decides what to do with
 * the date, and a sweep asks again later, because a parcel delivered
 * late moves the date and an open dispute holds it.
 */
final class ClosureWindow
{
    /** The last moment any order may still need the seller, or null if none. */
    public static function endsAt(Seller $seller): ?CarbonInterface
    {
        $window = self::disputeDays();

        $orders = Order::query()
            ->where('seller_id', $seller->id)
            ->where(fn (Builder $query) => $query
                ->where('status', Order::STATUS_DISPATCHED)
                ->orWhere(fn (Builder $delivered) => $delivered
                    ->where('status', Order::STATUS_DELIVERED)
                    ->where('delivered_at', '>', now()->subDays($window))))
            ->get(['id', 'status', 'dispatched_at', 'delivered_at', 'shipping_service', 'shipping_destination']);

        if ($orders->isEmpty()) {
            return null;
        }

        $transit = self::transitTable($seller);

        $latest = $orders
            ->map(fn (Order $order): ?CarbonInterface => match ($order->status) {
                Order::STATUS_DELIVERED => $order->delivered_at?->copy()->addDays($window),
                default => ($order->dispatched_at ?? now())
                    ->copy()
                    ->addDays(self::transitDays($order, $transit) + $window),
            })
            ->filter()
            ->sortDesc()
            ->first();

        return $latest !== null && $latest->isFuture() ? $latest : null;
    }

    /**
     * A dispute still being worked holds the account open, whatever the
     * dates say: closing under it would leave the case with no seller.
     */
    public static function hasOpenCases(Seller $seller): bool
    {
        return SupportCase::query()
            ->pending()
            ->where(fn (Builder $query) => $query
                ->where('seller_id', $seller->id)
                ->orWhereHas('listing', fn (Builder $listing) => $listing->where('seller_id', $seller->id)))
            ->exists();
    }

    public static function disputeDays(): int
    {
        return (int) config('verum_cases.disputes.window_days', 30);
    }

    /**
     * The seller's own quoted maximum for this service and zone, else the
     * platform default for the service, else the hard ceiling — the
     * generous answer, because closing early is the costly mistake.
     *
     * @param  array<string, int>  $transit
     */
    private static function transitDays(Order $order, array $transit): int
    {
        $key = $order->shipping_service.'|'.$order->shipping_destination;

        return $transit[$key]
            ?? (int) (config("verum_shipping.services.{$order->shipping_service}.default_days")[1]
                ?? config('verum_shipping.limits.max_transit_days'));
    }

    /** @return array<string, int> "service|destination" => max days */
    private static function transitTable(Seller $seller): array
    {
        return ShippingRate::query()
            ->where('seller_id', $seller->id)
            ->get(['service', 'destination', 'transit_max_days'])
            ->mapWithKeys(fn (ShippingRate $rate): array => [
                $rate->service.'|'.$rate->destination => (int) $rate->transit_max_days,
            ])
            ->all();
    }
}
