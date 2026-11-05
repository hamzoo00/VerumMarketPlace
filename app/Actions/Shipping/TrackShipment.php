<?php

namespace App\Actions\Shipping;

use App\Actions\Orders\AdvanceOrder;
use App\Contracts\ParcelTracker;
use App\Jobs\PushTrackingToPayPal;
use App\Jobs\RegisterParcel;
use App\Models\Order;
use App\Models\SellerAccount;
use App\Models\ShipmentEvent;
use App\Notifications\ShipmentUpdate;
use App\Support\Carriers;
use App\Support\ClosureWindow;
use App\Support\MessageModeration;
use App\Support\ShipmentStatuses;
use App\Support\Tracking\CourierScan;
use App\Support\Tracking\ParcelReport;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A parcel's journey, from the seller's counter to the buyer's door.
 *
 * The only place `shipment_events` is written and `orders.tracking_status`
 * changes, whoever reports the step — the seller's form, a courier
 * webhook, the poll, or the local simulator — so they cannot disagree
 * about what a step means or who is told.
 *
 * Two rules keep the noise down and the record honest:
 *
 * - **A courier scan announces a change, not a scan.** A parcel is
 *   scanned a dozen times in transit; the buyer hears when it reaches
 *   customs or goes out for delivery, not each time it is.
 * - **A seller's update always reaches the buyer.** They wrote it for
 *   them. It is labelled as the seller's word beside the courier's scans,
 *   so a buyer can weigh one against the other.
 */
class TrackShipment
{
    public function __construct(
        private readonly AdvanceOrder $advance,
        private readonly ParcelTracker $tracker,
    ) {}

    /**
     * The seller has handed the parcel over: the first step, the buyer's
     * link, and the courier API and PayPal given the number.
     *
     * @throws RuntimeException when the order is not ready to post
     */
    public function dispatch(Order $order, SellerAccount $by, ?string $carrier, ?string $number): Order
    {
        $number = filled($number) ? Carriers::normaliseNumber((string) $number) : null;

        $order = DB::transaction(function () use ($order, $by, $carrier, $number): Order {
            $posted = $this->advance->markDispatched($order, $carrier, $number);

            $this->write($posted, ShipmentEvent::SOURCE_SELLER, 'posted', $number === null
                ? __('Posted without a tracking number.')
                : __('Handed to :carrier, tracking number :number.', ['carrier' => $this->carrier($posted), 'number' => $number]), null, now(), $by);
            $this->settle($posted);

            return $posted;
        });

        $this->watch($order);

        $this->tellBuyer($order, 'posted', $number === null
            ? __(':seller has posted your order without a tracking number. Ask them in messages if you would like one.', ['seller' => $order->seller->name ?? __('The seller')])
            : __(':seller posted your order with :carrier. Tracking number :number.', [
                'seller' => $order->seller->name ?? __('The seller'),
                'carrier' => $this->carrier($order),
                'number' => $number,
            ]), towardsCourier: true);

        return $order;
    }

    /**
     * The seller says where the parcel has got to, or adds a note.
     *
     * @throws RuntimeException when the update does not fit the order, or
     *                          carries contact details
     */
    public function sellerUpdate(Order $order, SellerAccount $by, string $status, ?string $description, ?string $location): void
    {
        if (! array_key_exists($status, ShipmentStatuses::pickableFor($order))) {
            throw new RuntimeException(__('That update does not fit an order that is :status.', [
                'status' => Str::lower($order->statusLabel()),
            ]));
        }

        $description = $this->clean($description, 'description');
        $location = $this->clean($location, 'location');

        if (! ShipmentStatuses::isState($status) && $description === null) {
            throw new RuntimeException(__('Write the note for the buyer.'));
        }

        $this->refuseContactDetails($description, $location);

        DB::transaction(function () use ($order, $by, $status, $description, $location): void {
            $this->write($order, ShipmentEvent::SOURCE_SELLER, $status, $description, $location, now(), $by);
            $this->settle($order);
        });

        $this->tellBuyer($order, $status, $this->sellerLine($status, $description, $location), towardsCourier: false);
    }

    /** The seller's "Mark as delivered". */
    public function delivered(Order $order, SellerAccount $by): Order
    {
        $order = DB::transaction(function () use ($order, $by): Order {
            $delivered = $this->advance->markDelivered($order);

            $this->write($delivered, ShipmentEvent::SOURCE_SELLER, 'delivered', __('Marked as delivered by the seller.'), null, now(), $by);
            $this->settle($delivered);

            return $delivered;
        });

        $this->tellBuyer($order, 'delivered', __('The seller marked your order as delivered. If it has not arrived, message them first — you can open a dispute within :days days.', [
            'days' => ClosureWindow::disputeDays(),
        ]), towardsCourier: false);

        return $order;
    }

    /**
     * A mistyped number, put right while the parcel is still moving.
     *
     * The old number stays on the record as part of the step, and both
     * the courier API and PayPal are given the new one.
     *
     * @throws RuntimeException
     */
    public function retrack(Order $order, SellerAccount $by, string $carrier, string $number): Order
    {
        if (! $order->isInTransit()) {
            throw new RuntimeException(__('The tracking number can only be corrected while the parcel is on its way.'));
        }

        $number = Carriers::normaliseNumber($number);

        if ($number === $order->tracking_number && $carrier === $order->tracking_carrier) {
            throw new RuntimeException(__('That is already the tracking number on this order.'));
        }

        $was = trim($this->carrier($order).' '.$order->tracking_number);

        $order = DB::transaction(function () use ($order, $by, $carrier, $number, $was): Order {
            $order->update([
                'tracking_carrier' => $carrier,
                'tracking_number' => $number,
                'tracking_registered_at' => null,
                'tracking_synced_at' => null,
            ]);

            // PayPal is given the corrected number as well.
            $order->payments()->update(['tracking_sent_at' => null]);

            $this->write($order, ShipmentEvent::SOURCE_SELLER, 'retracked', __('Now :carrier :number (was :was).', [
                'carrier' => $this->carrier($order),
                'number' => $number,
                'was' => $was,
            ]), null, now(), $by);

            return $order;
        });

        $this->watch($order);

        $this->tellBuyer($order, 'retracked', __('The seller corrected the tracking number to :carrier :number. The tracking link now follows the new one.', [
            'carrier' => $this->carrier($order),
            'number' => $number,
        ]), towardsCourier: true);

        return $order;
    }

    /**
     * What a courier reported about a number, applied to every order sent
     * under it. Safe to repeat: a scan already recorded is skipped.
     *
     * @return int scans not seen before
     */
    public function courier(ParcelReport $report): int
    {
        $added = 0;

        Order::query()
            ->where('tracking_number', Carriers::normaliseNumber($report->number))
            ->whereIn('status', [Order::STATUS_DISPATCHED, Order::STATUS_DELIVERED])
            ->each(function (Order $order) use ($report, &$added): void {
                $added += $this->applyScans($order, $report->scans);
            });

        return $added;
    }

    /**
     * Records the scans and moves the order on, with the row locked: a
     * webhook and the poll reporting the same scan at once must not both
     * see it as new and tell the buyer twice.
     *
     * @param  list<CourierScan>  $scans
     */
    private function applyScans(Order $order, array $scans): int
    {
        /** @var array{0: int, 1: string|null, 2: ShipmentEvent|null, 3: Order} $result */
        $result = DB::transaction(function () use ($order, $scans): array {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $added = 0;

            foreach ($scans as $scan) {
                $added += $this->write($locked, ShipmentEvent::SOURCE_COURIER, $scan->status, $scan->description, $scan->location, $scan->occurredAt, null, $scan->fingerprint());
            }

            $locked->update(['tracking_synced_at' => now()]);

            if ($added === 0) {
                return [0, null, null, $locked];
            }

            [$before, $latest] = $this->settle($locked);

            // The courier's scan is the better evidence of delivery, and
            // its time is the one the dispute window counts from.
            if ($latest?->status === 'delivered' && $locked->isInTransit()) {
                $locked = $this->advance->markDelivered($locked, $latest->occurred_at);
            }

            return [$added, $before, $latest, $locked];
        });

        [$added, $before, $latest, $fresh] = $result;

        if ($latest !== null && $latest->status !== $before) {
            $line = $this->scanLine($fresh, $latest);

            $this->tellBuyer($fresh, $latest->status, $line, towardsCourier: true);
            $this->tellSeller($fresh, $latest->status, $line);
        }

        return $added;
    }

    /**
     * Brings `tracking_status` up to the newest step that says where the
     * parcel is. Run inside the transaction that wrote the step; the row
     * lock makes a concurrent report wait and then see this one.
     *
     * @return array{0: string|null, 1: ShipmentEvent|null} the status before, and the newest step
     */
    private function settle(Order $order): array
    {
        $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
        $before = $locked->tracking_status;

        $latest = $locked->shipmentEvents()
            ->whereIn('status', ShipmentStatuses::states())
            ->first();

        if ($latest !== null) {
            $now = ['tracking_status' => $latest->status, 'tracking_status_at' => $latest->occurred_at];

            $locked->update($now);
            $order->forceFill($now);
        }

        return [$before, $latest];
    }

    /** @return int 1 when the step is new, 0 when it was already recorded */
    private function write(Order $order, string $source, string $status, ?string $description, ?string $location, CarbonInterface $at, ?SellerAccount $by, ?string $fingerprint = null): int
    {
        return DB::table('shipment_events')->insertOrIgnore([
            'order_id' => $order->id,
            'source' => $source,
            'status' => $status,
            // A courier's text is outside input like a seller's.
            'description' => $description === null ? null : Str::limit(strip_tags($description), 500, ''),
            'location' => $location === null ? null : Str::limit(strip_tags($location), 160, ''),
            'occurred_at' => $at,
            'recorded_by' => $by?->id,
            'fingerprint' => $fingerprint ?? hash('sha256', (string) Str::uuid()),
            'created_at' => now(),
        ]);
    }

    /** Has the courier API watch the number, and gives it to PayPal. */
    private function watch(Order $order): void
    {
        if (blank($order->tracking_number)) {
            return;
        }

        if ($this->tracker->watches()) {
            RegisterParcel::dispatch($order->id, (string) $order->tracking_number);
        }

        // PayPal protects the seller against "never arrived" with this.
        PushTrackingToPayPal::dispatch($order->id);
    }

    /**
     * A scan goes to the carrier's own page from the bell, a seller's
     * words to the order where they are written.
     */
    private function tellBuyer(Order $order, string $status, string $body, bool $towardsCourier): void
    {
        $channel = ShipmentStatuses::channelFor($status, 'buyer');
        $buyer = $order->buyer;

        if ($channel === null || $buyer === null) {
            return;
        }

        $orderUrl = route('buyer.orders.show', $order);
        $tracking = $order->trackingUrl();

        $buyer->notify(new ShipmentUpdate(
            $status,
            ShipmentStatuses::headline($status, $order->reference),
            $body,
            $towardsCourier && $tracking !== null ? route('buyer.orders.track', $order) : $orderUrl,
            $orderUrl,
            $tracking,
            $channel === 'mail',
        ));
    }

    private function tellSeller(Order $order, string $status, string $body): void
    {
        $channel = ShipmentStatuses::channelFor($status, 'seller');
        $account = $order->seller?->account;

        if ($channel === null || $account === null) {
            return;
        }

        $url = route('seller.orders.show', $order);

        $account->notify(new ShipmentUpdate($status, ShipmentStatuses::headline($status, $order->reference), $body, $url, $url, email: $channel === 'mail'));
    }

    private function scanLine(Order $order, ShipmentEvent $scan): string
    {
        $replace = [
            'carrier' => $this->carrier($order),
            'what' => $scan->description ?? $scan->statusLabel(),
            'where' => $scan->location,
        ];

        return $scan->location === null
            ? __(':carrier: :what', $replace)
            : __(':carrier: :what — :where', $replace);
    }

    private function sellerLine(string $status, ?string $description, ?string $location): string
    {
        $line = $description === null
            ? __('The seller marked it “:status”.', ['status' => ShipmentStatuses::label($status)])
            : __('The seller writes: “:text”', ['text' => $description]);

        return $location === null ? $line : $line.' '.__('(:where)', ['where' => $location]);
    }

    private function carrier(Order $order): string
    {
        return Carriers::name($order->tracking_carrier) ?? __('The carrier');
    }

    /**
     * A delivery update reaches the buyer outside messages, where the
     * contact-detail checks run, so it must not become a way round them.
     * Refused rather than flagged: unlike a message, nothing is lost by
     * moving the sentence to where it belongs.
     */
    private function refuseContactDetails(?string ...$texts): void
    {
        $text = trim(implode(' ', array_filter($texts)));

        if ($text !== '' && MessageModeration::inspect($text)->shouldWarn()) {
            throw new RuntimeException(__('Keep contact and payment details out of delivery updates. Say where the parcel is here; anything else goes in messages.'));
        }
    }

    private function clean(?string $text, string $limit): ?string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text)));

        return $text === '' ? null : Str::limit($text, (int) config('verum_tracking.limits.'.$limit), '');
    }
}
