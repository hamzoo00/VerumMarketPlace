<?php

use App\Actions\Shipping\TrackShipment;
use App\Jobs\PushTrackingToPayPal;
use App\Jobs\RegisterParcel;
use App\Models\Buyer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Models\ShipmentEvent;
use App\Notifications\ShipmentUpdate;
use App\Support\Tracking\CourierScan;
use App\Support\Tracking\ParcelReport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

/**
 * A parcel's journey: the seller posts it and says where it has got to,
 * the courier's scans arrive, and the buyer hears about it — by email
 * for what matters, in the bell for the rest, and once per change.
 */
beforeEach(function (): void {
    config(['verum_tracking.driver' => 'fake']);
    Notification::fake();

    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->buyer = Buyer::factory()->create();
});

function postedOrder(Seller $seller, Buyer $buyer): Order
{
    return Order::factory()->dispatched()->for($seller)->for($buyer)->create([
        'tracking_carrier' => 'dhl',
        'tracking_number' => 'JD0146000012',
        'tracking_status' => 'posted',
    ]);
}

function scan(string $status, string $description, string $at, ?string $where = null): CourierScan
{
    return new CourierScan($status, $description, $where, CarbonImmutable::parse($at));
}

it('posts the parcel: a first step, the number tidied, and the buyer emailed the carrier’s link', function (): void {
    Queue::fake([RegisterParcel::class, PushTrackingToPayPal::class]);
    $order = Order::factory()->paid()->for($this->seller)->for($this->buyer)->create();

    $this->actingAs($this->account, 'seller')
        ->put(route('seller.orders.update', $order), [
            'action' => 'dispatched',
            'tracking_carrier' => 'dhl',
            'tracking_number' => 'jd01 4600 0012',
        ])
        ->assertSessionHas('status', 'Marked as dispatched. The buyer has the tracking link.');

    $order->refresh();

    expect($order->tracking_number)->toBe('JD0146000012')
        ->and($order->tracking_status)->toBe('posted')
        ->and($order->shipmentEvents()->sole())
        ->source->toBe(ShipmentEvent::SOURCE_SELLER)
        ->status->toBe('posted')
        ->recorded_by->toBe($this->account->id);

    Queue::assertPushed(RegisterParcel::class, fn (RegisterParcel $job): bool => $job->number === 'JD0146000012');

    Notification::assertSentTo($this->buyer, ShipmentUpdate::class, function (ShipmentUpdate $notification) use ($order): bool {
        $mail = $notification->toMail($this->buyer);

        return $notification->via($this->buyer) === ['mail', 'database', 'broadcast']
            && $mail->actionUrl === 'https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id=JD0146000012'
            && $notification->toArray($this->buyer)['url'] === route('buyer.orders.track', $order);
    });
});

it('shows the seller’s update to the buyer, marked as the seller’s, with a bell but no email', function (): void {
    $order = postedOrder($this->seller, $this->buyer);

    $this->actingAs($this->account, 'seller')
        ->post(route('seller.orders.shipment.store', $order), [
            'status' => 'customs',
            'location' => 'Colombo',
            'description' => 'Customs asked for the invoice; sent it this morning.',
        ])
        ->assertSessionHas('status', 'Update recorded. The buyer can see it now.');

    expect($order->fresh()->tracking_status)->toBe('customs');

    Notification::assertSentTo($this->buyer, ShipmentUpdate::class, fn (ShipmentUpdate $notification): bool => $notification->via($this->buyer) === ['database', 'broadcast']
        && $notification->toArray($this->buyer)['url'] === route('buyer.orders.show', $order));

    auth()->guard('seller')->logout();

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.orders.show', $order))
        ->assertOk()
        ->assertSeeInOrder(['At customs', 'Seller', 'Customs asked for the invoice; sent it this morning.', 'Colombo']);
});

it('keeps markup out of what the seller writes', function (): void {
    $order = postedOrder($this->seller, $this->buyer);

    $this->actingAs($this->account, 'seller')
        ->post(route('seller.orders.shipment.store', $order), [
            'status' => 'note',
            'description' => '<script>alert(1)</script>Left the sorting office',
        ]);

    expect($order->shipmentEvents()->where('status', 'note')->value('description'))->toBe('alert(1)Left the sorting office');

    auth()->guard('seller')->logout();

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.orders.show', $order))
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('refuses contact details in a delivery update', function (): void {
    $order = postedOrder($this->seller, $this->buyer);

    $this->actingAs($this->account, 'seller')
        ->post(route('seller.orders.shipment.store', $order), [
            'status' => 'note',
            'description' => 'Delayed, whatsapp me on 03315084546 for details',
        ])
        ->assertSessionHas('error', 'Keep contact and payment details out of delivery updates. Say where the parcel is here; anything else goes in messages.');

    expect($order->shipmentEvents()->count())->toBe(0);
    Notification::assertNothingSentTo($this->buyer);
});

it('offers only what fits the stage: preparing before posting, movement after', function (): void {
    $order = Order::factory()->paid()->for($this->seller)->for($this->buyer)->create();

    $this->actingAs($this->account, 'seller')
        ->post(route('seller.orders.shipment.store', $order), ['status' => 'in_transit'])
        ->assertSessionHasErrors(['status' => 'Choose what has happened to the parcel.']);

    $this->post(route('seller.orders.shipment.store', $order), ['status' => 'preparing'])
        ->assertSessionHas('status');

    expect($order->fresh()->tracking_status)->toBe('preparing');
});

it('asks for the words when the update is a note', function (): void {
    $order = postedOrder($this->seller, $this->buyer);

    $this->actingAs($this->account, 'seller')
        ->post(route('seller.orders.shipment.store', $order), ['status' => 'note', 'description' => '   '])
        ->assertSessionHas('error', 'Write the note for the buyer.');
});

it('keeps another seller away from an order’s parcel', function (): void {
    $order = postedOrder($this->seller, $this->buyer);
    $other = SellerAccount::factory()->create(['approved_at' => now()]);
    Seller::factory()->create(['seller_account_id' => $other->id]);

    $this->actingAs($other, 'seller')
        ->post(route('seller.orders.shipment.store', $order), ['status' => 'in_transit'])
        ->assertForbidden();

    expect($order->shipmentEvents()->count())->toBe(0);
});

it('corrects a mistyped number: recorded, re-registered, given to PayPal again and sent to the buyer', function (): void {
    Queue::fake([RegisterParcel::class, PushTrackingToPayPal::class]);
    $order = postedOrder($this->seller, $this->buyer);
    $payment = Payment::factory()->completed()->for($order)->create(['tracking_sent_at' => now()]);
    $order->update(['tracking_registered_at' => now()]);

    $this->actingAs($this->account, 'seller')
        ->put(route('seller.orders.shipment.update', $order), [
            'tracking_carrier' => 'fedex',
            'tracking_number' => '7712 3456 7890',
        ])
        ->assertSessionHas('status', 'Tracking number corrected. The buyer has the new link.');

    $order->refresh();

    expect($order->tracking_number)->toBe('771234567890')
        ->and($order->tracking_carrier)->toBe('fedex')
        ->and($order->tracking_registered_at)->toBeNull()
        ->and($payment->fresh()->tracking_sent_at)->toBeNull()
        ->and($order->shipmentEvents()->sole()->description)->toBe('Now FedEx 771234567890 (was DHL Express JD0146000012).');

    Queue::assertPushed(RegisterParcel::class, fn (RegisterParcel $job): bool => $job->number === '771234567890');
    Notification::assertSentTo($this->buyer, ShipmentUpdate::class, fn (ShipmentUpdate $notification): bool => $notification->toMail($this->buyer)->actionUrl === 'https://www.fedex.com/fedextrack/?trknbr=771234567890');
});

it('records each courier scan once and tells the buyer once, however often it is reported', function (): void {
    $order = postedOrder($this->seller, $this->buyer);
    $report = new ParcelReport('jd0146000012', [
        scan('in_transit', 'Departed facility', '2026-11-01 08:00', 'Colombo, LK'),
        scan('in_transit', 'Arrived at hub', '2026-11-01 20:00', 'Dubai, AE'),
    ]);

    $tracking = app(TrackShipment::class);

    expect($tracking->courier($report))->toBe(2)
        ->and($tracking->courier($report))->toBe(0)
        ->and($order->shipmentEvents()->count())->toBe(2)
        ->and($order->fresh()->tracking_status)->toBe('in_transit');

    Notification::assertSentToTimes($this->buyer, ShipmentUpdate::class, 1);
    Notification::assertNothingSentTo($this->account);
});

it('stays quiet when a new scan does not change where the parcel is', function (): void {
    $order = postedOrder($this->seller, $this->buyer);
    $tracking = app(TrackShipment::class);

    $tracking->courier(new ParcelReport('JD0146000012', [scan('in_transit', 'Departed facility', '2026-11-01 08:00')]));
    $tracking->courier(new ParcelReport('JD0146000012', [scan('in_transit', 'Arrived at hub', '2026-11-01 20:00')]));

    expect($order->shipmentEvents()->count())->toBe(2);
    Notification::assertSentToTimes($this->buyer, ShipmentUpdate::class, 1);
});

it('marks the order delivered at the courier’s scan time and tells both sides', function (): void {
    $this->travelTo('2026-11-02 09:00');
    $order = postedOrder($this->seller, $this->buyer);

    app(TrackShipment::class)->courier(new ParcelReport('JD0146000012', [
        scan('out_for_delivery', 'With courier', '2026-11-01 07:30', 'London, GB'),
        scan('delivered', 'Delivered — signed for', '2026-11-01 11:05', 'London, GB'),
    ]));

    $order->refresh();

    expect($order->status)->toBe(Order::STATUS_DELIVERED)
        ->and($order->delivered_at->toDateTimeString())->toBe('2026-11-01 11:05:00')
        ->and($order->tracking_status)->toBe('delivered');

    Notification::assertSentTo($this->buyer, ShipmentUpdate::class, fn (ShipmentUpdate $notification): bool => in_array('mail', $notification->via($this->buyer), true)
        && $notification->toArray($this->buyer)['body'] === 'DHL Express: Delivered — signed for — London, GB');
    Notification::assertSentTo($this->account, ShipmentUpdate::class, fn (ShipmentUpdate $notification): bool => $notification->via($this->account) === ['database', 'broadcast']);
});

it('lets a seller’s later note stand without becoming the parcel’s status', function (): void {
    $order = postedOrder($this->seller, $this->buyer);
    app(TrackShipment::class)->courier(new ParcelReport('JD0146000012', [scan('customs', 'Held by customs', now()->subHour()->toDateTimeString())]));

    $this->actingAs($this->account, 'seller')
        ->post(route('seller.orders.shipment.store', $order), ['status' => 'note', 'description' => 'Invoice sent to customs.']);

    expect($order->fresh()->tracking_status)->toBe('customs');
});

it('sends the buyer on to the carrier’s page, and no one else', function (): void {
    $order = postedOrder($this->seller, $this->buyer);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.orders.track', $order))
        ->assertRedirect('https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id=JD0146000012');

    $this->actingAs(Buyer::factory()->create(), 'buyer')
        ->get(route('buyer.orders.track', $order))
        ->assertForbidden();
});

it('scans a parcel from the courier simulator through the real path', function (): void {
    $order = postedOrder($this->seller, $this->buyer);

    $this->post(URL::signedRoute('tracking.simulator.scan', $order), ['status' => 'out_for_delivery', 'location' => 'Leeds'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Scanned: Out for delivery.');

    expect($order->fresh()->tracking_status)->toBe('out_for_delivery')
        ->and($order->shipmentEvents()->sole())
        ->source->toBe(ShipmentEvent::SOURCE_COURIER)
        ->location->toBe('Leeds');
});

it('opens the simulator only from a signed link, and never beside a real tracking API', function (): void {
    $order = postedOrder($this->seller, $this->buyer);

    $this->get(route('tracking.simulator', $order))->assertForbidden();

    config(['verum_tracking.driver' => '17track']);

    $this->get(URL::signedRoute('tracking.simulator', $order))->assertNotFound();
});

it('gives the seller the update form, the number fix and, in test mode, the simulator', function (): void {
    $order = postedOrder($this->seller, $this->buyer);

    $this->actingAs($this->account, 'seller')
        ->get(route('seller.orders.show', $order))
        ->assertOk()
        ->assertSee('Add update')
        ->assertSee('Wrong tracking number?')
        ->assertSee('Courier simulator (test mode)');
});
