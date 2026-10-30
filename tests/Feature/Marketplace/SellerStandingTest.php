<?php

use App\Actions\Orders\PlaceOrder;
use App\Models\AccountNotice;
use App\Models\Admin;
use App\Models\BlockedEmail;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Models\SupportCase;
use App\Notifications\AccountNoticeSent;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

/**
 * Warn, suspend, reinstate, close — what an admin can do to a seller.
 *
 * Pinned here: each decision is told to the seller and kept on record,
 * a suspension hides the storefront without touching listing rows, and
 * closing winds down rather than switching off — undispatched orders are
 * cancelled with an apology, parcels on the road keep the account open.
 */
beforeEach(function (): void {
    $this->admin = Admin::factory()->create();
    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);

    $this->listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'price_cents' => 50_000,
    ]);
});

function decideStanding(SellerAccount $account, string $action, ?string $message = null): TestResponse
{
    return test()->actingAs(test()->admin, 'admin')
        ->put(route('admin.sellers.update', $account), array_filter([
            'action' => $action,
            'message' => $message,
        ]));
}

it('warns a seller with the standard message and counts it', function (): void {
    Notification::fake();

    decideStanding($this->account, 'warn')->assertRedirect()->assertSessionHas('status');
    decideStanding($this->account, 'warn')->assertRedirect();

    $notice = AccountNotice::query()->for($this->account)->latest('id')->first();

    expect(AccountNotice::warningCountFor($this->account))->toBe(2)
        ->and($notice->used_default)->toBeTrue()
        ->and($notice->admin_id)->toBe($this->admin->id);

    Notification::assertSentToTimes($this->account, AccountNoticeSent::class, 2);
});

it('sends and keeps the admin’s own words when they write some', function (): void {
    Notification::fake();

    decideStanding($this->account, 'warn', 'Please stop asking buyers for WhatsApp.')->assertRedirect();

    $notice = AccountNotice::query()->for($this->account)->firstOrFail();

    expect($notice->body)->toBe('Please stop asking buyers for WhatsApp.')
        ->and($notice->used_default)->toBeFalse();
});

it('strips markup from an admin message before it is stored or sent', function (): void {
    Notification::fake();

    decideStanding($this->account, 'warn', 'Read the rules <script>alert(1)</script>')->assertRedirect();

    expect(AccountNotice::query()->for($this->account)->value('body'))
        ->not->toContain('<script>')
        ->toStartWith('Read the rules');
});

it('answers a live request with JSON instead of a redirect', function (): void {
    Notification::fake();

    $this->actingAs($this->admin, 'admin')
        ->putJson(route('admin.sellers.update', $this->account), ['action' => 'warn'])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Warning sent'));
});

it('hides a suspended seller’s storefront and listings without touching them', function (): void {
    Notification::fake();

    decideStanding($this->account, 'suspend')->assertRedirect();

    expect($this->account->fresh()->isSuspended())->toBeTrue()
        ->and(Listing::query()->published()->whereKey($this->listing->id)->exists())->toBeFalse()
        // The row itself is unchanged, so reinstating restores it exactly.
        ->and($this->listing->fresh()->status)->toBe(Listing::STATUS_ACTIVE);

    $this->get(route('sellers.show', $this->seller))->assertNotFound();
    Notification::assertSentTo($this->account, AccountNoticeSent::class);
});

it('refuses a new order for a suspended seller even from an open page', function (): void {
    Notification::fake();
    decideStanding($this->account, 'suspend');

    app(PlaceOrder::class)->handle(Buyer::factory()->create(), $this->listing, [
        'name' => 'Ada', 'line1' => '1 Road', 'city' => 'Colombo', 'country' => 'LK',
    ]);
})->throws(RuntimeException::class, 'not taking orders');

it('keeps a suspended seller out of the listing form but in their dashboard', function (): void {
    Notification::fake();
    decideStanding($this->account, 'suspend');

    $this->actingAs($this->account->fresh(), 'seller')
        ->get(route('seller.listings.create'))
        ->assertRedirect(route('seller.dashboard'))
        ->assertSessionHas('error');

    $this->actingAs($this->account->fresh(), 'seller')
        ->get(route('seller.dashboard'))
        ->assertOk()
        ->assertSee('Your selling is suspended.');
});

it('puts the storefront back exactly when a seller is reinstated', function (): void {
    Notification::fake();
    decideStanding($this->account, 'suspend');

    decideStanding($this->account, 'reinstate')->assertRedirect();

    expect($this->account->fresh()->canTrade())->toBeTrue()
        ->and(Listing::query()->published()->whereKey($this->listing->id)->exists())->toBeTrue();
});

it('refuses to reinstate a seller who was never suspended', function (): void {
    decideStanding($this->account, 'reinstate')->assertSessionHasErrors('standing');
});

it('closes at once when nothing is on its way, and blocks the email', function (): void {
    Notification::fake();

    decideStanding($this->account, 'close')->assertRedirect();

    $account = $this->account->fresh();

    expect($account->isClosed())->toBeTrue()
        ->and(BlockedEmail::isBlocked($account->email))->toBeTrue()
        ->and($this->listing->fresh()->status)->toBe(Listing::STATUS_PAUSED);

    $this->post(route('seller.login'), ['email' => $account->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'This account has been closed.']);
});

it('cancels undispatched orders on closing and apologises to each buyer', function (): void {
    Notification::fake();

    $buyer = Buyer::factory()->create();
    $waiting = Order::factory()->for($this->seller)->for($buyer)->create();
    $paid = Order::factory()->paid()->for($this->seller)->for($buyer)->create();

    decideStanding($this->account, 'close')->assertRedirect();

    expect($waiting->fresh()->status)->toBe(Order::STATUS_CANCELLED)
        ->and($paid->fresh()->status)->toBe(Order::STATUS_CANCELLED)
        ->and(AccountNotice::query()->for($buyer)->where('kind', 'order_cancelled_closure')->count())->toBe(2);

    Notification::assertSentToTimes($buyer, AccountNoticeSent::class, 2);
});

it('stays open for a parcel on the road until it can arrive and be disputed', function (): void {
    Notification::fake();
    $this->freezeTime();

    // Standard post's slowest quote is 21 days, then 30 to dispute.
    $order = Order::factory()->dispatched()->for($this->seller)->create(['shipping_service' => 'standard']);

    decideStanding($this->account, 'close')->assertRedirect();

    $account = $this->account->fresh();

    expect($account->isClosing())->toBeTrue()
        ->and($account->closes_at->toDateString())->toBe(now()->addDays(51)->toDateString())
        ->and($order->fresh()->status)->toBe(Order::STATUS_DISPATCHED)
        // Winding down, they can still reach the orders they must finish.
        ->and($account->canAccessDashboard())->toBeTrue()
        ->and($account->canTrade())->toBeFalse();
});

it('finishes a closure once the window has passed, not before', function (): void {
    Notification::fake();
    $this->freezeTime();

    Order::factory()->dispatched()->for($this->seller)->create(['shipping_service' => 'express']);
    decideStanding($this->account, 'close');

    $this->travel(10)->days();
    $this->artisan('sellers:finish-closures')->assertSuccessful();
    expect($this->account->fresh()->isClosed())->toBeFalse();

    // Express is 7 days at most, then 30 to dispute.
    $this->travel(28)->days();
    $this->artisan('sellers:finish-closures')->assertSuccessful();
    expect($this->account->fresh()->isClosed())->toBeTrue();
});

it('holds a closure open while a case about the seller is unresolved', function (): void {
    Notification::fake();
    $this->freezeTime();

    decideStanding($this->account, 'close');
    $this->account->update(['closed_at' => null, 'closes_at' => now()]);
    SupportCase::factory()->create(['seller_id' => $this->seller->id]);

    $this->artisan('sellers:finish-closures')->assertSuccessful();

    expect($this->account->fresh()->isClosed())->toBeFalse();
});

it('signs out a session that was open when the account closed', function (): void {
    Notification::fake();
    decideStanding($this->account, 'close');

    $this->actingAs($this->account->fresh(), 'seller')
        ->get(route('seller.dashboard'))
        ->assertRedirect(route('seller.login'));
});

it('refuses the standing actions to anyone but an admin', function (string $guard): void {
    $user = $guard === 'buyer' ? Buyer::factory()->create() : $this->account;

    $this->actingAs($user, $guard)
        ->put(route('admin.sellers.update', $this->account), ['action' => 'suspend'])
        ->assertRedirect(route('admin.login'));

    expect($this->account->fresh()->isSuspended())->toBeFalse();
})->with(['buyer', 'seller']);

it('lists sellers with their standing and warning count', function (): void {
    Notification::fake();
    decideStanding($this->account, 'warn');
    decideStanding($this->account, 'suspend');

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.sellers', ['standing' => SellerAccount::STANDING_SUSPENDED]))
        ->assertOk()
        ->assertSee($this->account->email)
        ->assertSee('Suspended')
        ->assertSee('Reinstate selling');
});
