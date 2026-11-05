<?php

use App\Contracts\ParcelTracker;
use App\Jobs\RegisterParcel;
use App\Models\Buyer;
use App\Models\Order;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Notifications\ShipmentUpdate;
use App\Support\Tracking\TrackingFailed;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;

/**
 * The real 17TRACK driver, against a faked 17TRACK API.
 *
 * Pins what a push may change (nothing until its signature checks out on
 * the raw body), how 17TRACK's statuses read here, and what the poll
 * asks for: only parcels that are due, in one request per forty.
 */
beforeEach(function (): void {
    config([
        'verum_tracking.driver' => '17track',
        'services.seventeentrack.key' => 'SECRET-17',
    ]);

    Notification::fake();
    Http::preventStrayRequests();
    $this->travelTo('2026-11-02 12:00');

    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->buyer = Buyer::factory()->create();
    $this->order = Order::factory()->dispatched()->for($this->seller)->for($this->buyer)->create([
        'tracking_carrier' => 'dhl',
        'tracking_number' => 'JD0146000012',
        'tracking_status' => 'posted',
        'tracking_registered_at' => now()->subDay(),
    ]);
});

/** @param  array<string, mixed>  $payload */
function seventeenTrackPush(array $payload, string $key = 'SECRET-17'): TestResponse
{
    $raw = (string) json_encode($payload);

    return test()->call('POST', route('webhooks.tracking'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_SIGN' => hash('sha256', $raw.'/'.$key),
    ], $raw);
}

/**
 * @param  list<array<string, mixed>>  $events
 * @return array<string, mixed>
 */
function trackInfo(string $status, string $subStatus, array $events): array
{
    return [
        'latest_status' => ['status' => $status, 'sub_status' => $subStatus],
        'tracking' => ['providers' => [['provider' => ['key' => 100001], 'events' => $events]]],
    ];
}

it('applies a signed push, reading customs from 17TRACK’s sub-status', function (): void {
    seventeenTrackPush([
        'event' => 'TRACKING_UPDATED',
        'data' => [
            'number' => 'JD0146000012',
            'carrier' => 100001,
            'track_info' => trackInfo('InTransit', 'InTransit_CustomsProcessing', [
                ['time_utc' => '2026-11-01T03:30:00Z', 'description' => 'Shipment picked up', 'location' => 'COLOMBO - SRI LANKA', 'stage' => 'PickedUp', 'sub_status' => 'InTransit_PickedUp'],
                ['time_utc' => '2026-11-02T06:10:00Z', 'description' => 'Customs status updated', 'location' => 'LONDON-HEATHROW - UK', 'stage' => null, 'sub_status' => 'InTransit_CustomsProcessing'],
            ]),
        ],
    ])->assertOk();

    expect($this->order->shipmentEvents()->pluck('status')->all())->toBe(['customs', 'in_transit'])
        ->and($this->order->fresh()->tracking_status)->toBe('customs');

    Notification::assertSentTo($this->buyer, ShipmentUpdate::class);
    Notification::assertSentTo($this->account, ShipmentUpdate::class);
});

it('ignores a push 17TRACK did not sign', function (): void {
    seventeenTrackPush([
        'event' => 'TRACKING_UPDATED',
        'data' => ['number' => 'JD0146000012', 'track_info' => trackInfo('Delivered', 'Delivered_Other', [
            ['time_utc' => '2026-11-02T06:10:00Z', 'description' => 'Delivered', 'stage' => 'Delivered', 'sub_status' => 'Delivered_Other'],
        ])],
    ], key: 'GUESSED')->assertBadRequest();

    expect($this->order->fresh()->status)->toBe(Order::STATUS_DISPATCHED)
        ->and($this->order->shipmentEvents()->count())->toBe(0);
});

it('lets 17TRACK post without a CSRF token', function (): void {
    // Tests skip CSRF entirely; the route's resolved middleware is what
    // 17TRACK actually meets.
    $route = Route::getRoutes()->getByName('webhooks.tracking');

    expect(app('router')->gatherRouteMiddleware($route))->not->toContain(PreventRequestForgery::class);
});

it('polls only the parcels that are due, and registers the ones never registered', function (): void {
    Queue::fake([RegisterParcel::class]);
    Sleep::fake();

    $recent = Order::factory()->dispatched()->for($this->seller)->create([
        'tracking_number' => 'RECENT0001',
        'tracking_registered_at' => now()->subDay(),
        'tracking_synced_at' => now()->subHour(),
    ]);
    $unregistered = Order::factory()->dispatched()->for($this->seller)->create(['tracking_number' => 'NEW0000001']);

    Http::fake([
        'https://api.17track.net/track/v2.4/gettrackinfo' => Http::response(['code' => 0, 'data' => [
            'accepted' => [[
                'number' => 'JD0146000012',
                'track_info' => trackInfo('Delivered', 'Delivered_Other', [
                    ['time_utc' => '2026-11-02T10:15:00Z', 'description' => 'Delivered', 'location' => 'LONDON - UK', 'stage' => 'Delivered', 'sub_status' => 'Delivered_Other'],
                ]),
            ]],
            'rejected' => [],
        ]]),
    ]);

    $this->artisan('tracking:sync')->assertSuccessful();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('17token', 'SECRET-17')
        && $request->data() === [['number' => 'JD0146000012', 'carrier' => 100001]]);

    expect($this->order->fresh())
        ->status->toBe(Order::STATUS_DELIVERED)
        ->tracking_synced_at->not->toBeNull()
        ->and($recent->fresh()->shipmentEvents()->count())->toBe(0);

    Queue::assertPushed(RegisterParcel::class, fn (RegisterParcel $job): bool => $job->orderId === $unregistered->id);
});

it('records how 17TRACK answers a registration', function (array $answer, bool $watched): void {
    $this->order->update(['tracking_registered_at' => null]);

    Http::fake(['https://api.17track.net/track/v2.4/register' => Http::response(['code' => 0, 'data' => $answer])]);

    (new RegisterParcel($this->order->id, 'JD0146000012'))->handle(app(ParcelTracker::class));

    $order = $this->order->fresh();

    expect($order->tracking_registered_at !== null)->toBe($watched)
        ->and($order->tracking_synced_at !== null)->toBe(! $watched);
})->with([
    'accepted' => [['accepted' => [['number' => 'JD0146000012']], 'rejected' => []], true],
    'already watching' => [['accepted' => [], 'rejected' => [['number' => 'JD0146000012', 'error' => ['code' => -18019901]]]], true],
    'no carrier found' => [['accepted' => [], 'rejected' => [['number' => 'JD0146000012', 'error' => ['code' => -18019903]]]], false],
]);

it('leaves a registration to the queue’s retry when 17TRACK is down', function (): void {
    Http::fake(['https://api.17track.net/track/v2.4/register' => Http::response('', 503)]);

    expect(fn () => (new RegisterParcel($this->order->id, 'JD0146000012'))->handle(app(ParcelTracker::class)))
        ->toThrow(TrackingFailed::class);
});

it('refuses the courier simulator in production', function (): void {
    config(['verum_tracking.driver' => 'fake']);
    app()->detectEnvironment(fn (): string => 'production');

    expect(fn () => app(ParcelTracker::class))->toThrow(RuntimeException::class, 'TRACKING_DRIVER must be "17track" or "off" in production.');
});
