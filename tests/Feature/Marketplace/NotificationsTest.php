<?php

use App\Models\Admin;
use App\Models\Buyer;
use App\Models\SellerAccount;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * The bell, for buyers, sellers and admins.
 *
 * Pinned here: each account only ever reaches its own notifications,
 * opening one marks it read and follows its link — but only to this
 * site — and the area in the URL decides whose bell a page shows, even
 * with several accounts signed in to one browser.
 */
beforeEach(function (): void {
    $this->buyer = Buyer::factory()->create();
});

/** A stored notification, as the database channel writes it. */
function notificationFor(Buyer|SellerAccount|Admin $account, array $data = [], bool $read = false): DatabaseNotification
{
    return $account->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\AccountNoticeSent',
        'data' => [
            'type' => 'notice.warning',
            'title' => 'A warning about your account',
            'body' => 'Please keep payment on the platform.',
            'url' => route('buyer.orders'),
            'icon' => 'user',
            ...$data,
        ],
        'read_at' => $read ? now() : null,
    ]);
}

it('shows a signed-in buyer their bell with the unread count', function (): void {
    notificationFor($this->buyer);
    notificationFor($this->buyer);
    notificationFor($this->buyer, read: true);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('data-bell', false)
        ->assertSee('Notifications, 2 unread')
        ->assertSee('A warning about your account');
});

it('shows a guest no bell at all', function (): void {
    $this->get(route('home'))->assertOk()->assertDontSee('data-bell', false);
});

it('marks a notification read when it is opened and follows its link', function (): void {
    $notification = notificationFor($this->buyer);

    $this->actingAs($this->buyer, 'buyer')
        ->post(route('buyer.notifications.read', $notification->id))
        ->assertRedirect(route('buyer.orders'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('never follows a link that leaves the site', function (string $url): void {
    $notification = notificationFor($this->buyer, ['url' => $url]);

    $this->actingAs($this->buyer, 'buyer')
        ->post(route('buyer.notifications.read', $notification->id))
        ->assertRedirect(route('buyer.notifications'));
})->with([
    'another host' => 'https://evil.test/phish',
    'protocol-relative' => '//evil.test/phish',
    'backslash trick' => '/\\evil.test',
    'script' => 'javascript:alert(1)',
]);

it('returns 404 for another account’s notification', function (): void {
    $theirs = notificationFor(Buyer::factory()->create());

    $this->actingAs($this->buyer, 'buyer')
        ->post(route('buyer.notifications.read', $theirs->id))
        ->assertNotFound();

    expect($theirs->fresh()->read_at)->toBeNull();
});

it('marks everything read at once', function (): void {
    notificationFor($this->buyer);
    notificationFor($this->buyer);

    $this->actingAs($this->buyer, 'buyer')
        ->postJson(route('buyer.notifications.read-all'))
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect($this->buyer->unreadNotifications()->count())->toBe(0);
});

it('lists every notification on the full page', function (): void {
    notificationFor($this->buyer, ['title' => 'Your order was cancelled']);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.notifications'))
        ->assertOk()
        ->assertSee('Your order was cancelled');
});

it('renders the bell alone for the live refresh', function (): void {
    notificationFor($this->buyer);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.notifications.bell'))
        ->assertOk()
        ->assertSee('data-bell-list', false)
        ->assertDontSee('<html', false);
});

it('shows the seller’s bell in the seller area even with a buyer signed in too', function (): void {
    $seller = SellerAccount::factory()->create(['approved_at' => now()]);
    notificationFor($this->buyer, ['title' => 'For the buyer']);
    notificationFor($seller, ['title' => 'For the seller', 'url' => null]);

    $this->actingAs($this->buyer, 'buyer')
        ->actingAs($seller, 'seller')
        ->get(route('seller.notifications'))
        ->assertOk()
        ->assertSee('For the seller')
        ->assertDontSee('For the buyer');
});

it('lets a seller still under review read their notifications', function (): void {
    $pending = SellerAccount::factory()->create(['approved_at' => null]);
    notificationFor($pending, ['title' => 'Your application needs something from you', 'url' => null]);

    $this->actingAs($pending, 'seller')
        ->get(route('seller.notifications'))
        ->assertOk()
        ->assertSee('Your application needs something from you');
});

it('sends a guest to sign in rather than showing any bell', function (): void {
    $this->get(route('buyer.notifications'))->assertRedirect(route('buyer.login'));
});

it('escapes notification text in the bell', function (): void {
    notificationFor($this->buyer, ['title' => '<script>alert("x")</script>Order']);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.notifications.bell'))
        ->assertOk()
        ->assertDontSee('<script>alert("x")</script>', false)
        ->assertSee('&lt;script&gt;', false);
});
