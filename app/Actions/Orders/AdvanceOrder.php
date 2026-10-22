<?php

namespace App\Actions\Orders;

use App\Models\Listing;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Moves an order along, one step at a time.
 *
 * The only place a status changes, so the seller panel, an admin and any
 * later API cannot invent a transition between them. Each step stamps
 * its own timestamp rather than leaving the status to imply a date — a
 * dispute asks *when* it was posted, not just whether.
 *
 * Transitions are checked, not assumed: an order cannot be dispatched
 * before it is paid, and nothing moves on once it is cancelled.
 */
class AdvanceOrder
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        Order::STATUS_AWAITING_PAYMENT => [Order::STATUS_PAID, Order::STATUS_CANCELLED],
        Order::STATUS_PAID => [Order::STATUS_DISPATCHED, Order::STATUS_CANCELLED],
        Order::STATUS_DISPATCHED => [Order::STATUS_DELIVERED],
        Order::STATUS_DELIVERED => [],
        Order::STATUS_CANCELLED => [],
    ];

    public function markPaid(Order $order): Order
    {
        return $this->move($order, Order::STATUS_PAID, ['paid_at' => now()]);
    }

    public function markDispatched(Order $order, ?string $carrier = null, ?string $tracking = null): Order
    {
        return $this->move($order, Order::STATUS_DISPATCHED, [
            'dispatched_at' => now(),
            'tracking_carrier' => $carrier,
            'tracking_number' => $tracking,
        ]);
    }

    public function markDelivered(Order $order): Order
    {
        return $this->move($order, Order::STATUS_DELIVERED, ['delivered_at' => now()]);
    }

    /**
     * Cancel, and put the stone back on sale.
     *
     * The listing was taken off the market when the order was placed, so
     * cancelling has to undo that or the seller silently loses the item.
     */
    public function cancel(Order $order, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $reason): Order {
            $cancelled = $this->move($order, Order::STATUS_CANCELLED, [
                'cancelled_at' => now(),
                'cancelled_reason' => $reason,
            ]);

            foreach ($cancelled->items as $item) {
                $listing = $item->listing;

                // Only a lot that is still sold-to-this-order goes back:
                // a seller may already have relisted it by hand.
                if ($listing !== null && $listing->status === Listing::STATUS_SOLD) {
                    $listing->update(['status' => Listing::STATUS_ACTIVE]);
                }
            }

            return $cancelled;
        });
    }

    /** @param  array<string, mixed>  $attributes */
    private function move(Order $order, string $to, array $attributes): Order
    {
        $from = (string) $order->status;

        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new RuntimeException(__('An order that is :from cannot become :to.', [
                'from' => $order->statusLabel(),
                'to' => Order::statusLabels()[$to] ?? $to,
            ]));
        }

        $order->update([...$attributes, 'status' => $to]);

        return $order->fresh(['items']) ?? $order;
    }
}
