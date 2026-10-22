<?php

use App\Actions\Orders\AdvanceOrder;
use App\Actions\Orders\OrderFromWin;
use App\Actions\Orders\PlaceOrder;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Models\ShippingRate;
use RuntimeException;

/**
 * Orders: the record of a sale.
 *
 * What is pinned here is that nothing the browser sends decides a price,
 * that a sale survives the listing changing underneath it, and that two
 * buyers cannot both own the same stone.
 */
beforeEach(function (): void {
    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
    $this->buyer = Buyer::factory()->create();

    $this->listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'price_cents' => 68_000,
        'currency' => 'USD',
        'shipping_mode' => Listing::SHIPPING_INHERIT,
    ]);

    $this->address = [
        'name' => 'A Buyer',
        'line1' => '1 Gem Street',
        'city' => 'Colombo',
        'country' => 'LK',
    ];

    $this->place = app(PlaceOrder::class);
});

it('prices the order from the listing, not from the request', function (): void {
    $order = $this->place->handle($this->buyer, $this->listing, $this->address);

    // The form carries an address and nothing else that costs money.
    expect($order->subtotal_cents)->toBe(68_000)
        ->and($order->total_cents)->toBe(68_000)
        ->and($order->status)->toBe(Order::STATUS_AWAITING_PAYMENT);
});

it('copies the title and price so the line survives the listing', function (): void {
    $order = $this->place->handle($this->buyer, $this->listing, $this->address);

    $this->listing->update(['title' => 'Renamed afterwards', 'price_cents' => 1_000]);

    $item = $order->fresh('items')->items->first();

    // What the buyer agreed to has to outlive a seller's edit.
    expect($item->title)->not->toBe('Renamed afterwards')
        ->and($item->unit_price_cents)->toBe(68_000);
});

it('takes the stone off the market', function (): void {
    $this->place->handle($this->buyer, $this->listing, $this->address);

    expect($this->listing->fresh()->status)->toBe(Listing::STATUS_SOLD);
});

it('refuses a second buyer for the same stone', function (): void {
    $this->place->handle($this->buyer, $this->listing, $this->address);

    $other = Buyer::factory()->create();

    expect(fn () => $this->place->handle($other, $this->listing->fresh(), $this->address))
        ->toThrow(RuntimeException::class);
});

it('refuses to sell an auction lot through checkout', function (): void {
    $lot = Listing::factory()->published()->auction()->for($this->seller)->for($this->category)->create();

    expect(fn () => $this->place->handle($this->buyer, $lot, $this->address))
        ->toThrow(RuntimeException::class);
});

it('refuses a draft listing', function (): void {
    $draft = Listing::factory()->for($this->seller)->for($this->category)->create([
        'status' => Listing::STATUS_DRAFT,
        'published_at' => null,
        'price_cents' => 5_000,
    ]);

    expect(fn () => $this->place->handle($this->buyer, $draft, $this->address))
        ->toThrow(RuntimeException::class);
});

it('adds the seller postage the buyer chose', function (): void {
    ShippingRate::factory()->create([
        'seller_id' => $this->seller->id,
        'service' => 'standard',
        'destination' => 'international',
        'price_cents' => 2_500,
        'is_active' => true,
    ]);

    $order = $this->place->handle($this->buyer, $this->listing, $this->address, shippingService: 'standard');

    expect($order->shipping_cents)->toBe(2_500)
        ->and($order->total_cents)->toBe(70_500);
});

it('ignores a postage service the seller does not offer', function (): void {
    ShippingRate::factory()->create([
        'seller_id' => $this->seller->id,
        'service' => 'standard',
        'destination' => 'international',
        'price_cents' => 2_500,
        'is_active' => true,
    ]);

    // A tampered value must not set its own price; it falls back to a
    // rate the seller actually published.
    $order = $this->place->handle($this->buyer, $this->listing, $this->address, shippingService: 'free-for-me');

    expect($order->shipping_cents)->toBe(2_500);
});

it('recomputes a discount from the coupon row', function (): void {
    Coupon::factory()->create([
        'seller_id' => $this->seller->id,
        'code' => 'WELCOME10',
        'type' => Coupon::TYPE_PERCENT,
        'percent' => 10,
    ]);

    $order = $this->place->handle($this->buyer, $this->listing, $this->address, couponCode: 'welcome10');

    expect($order->discount_cents)->toBe(6_800)
        ->and($order->coupon_code)->toBe('WELCOME10')
        ->and($order->total_cents)->toBe(61_200);
});

it('ignores another seller coupon', function (): void {
    $other = Seller::factory()->create();

    Coupon::factory()->create([
        'seller_id' => $other->id,
        'code' => 'NOTYOURS',
        'type' => Coupon::TYPE_PERCENT,
        'percent' => 50,
    ]);

    // A code is scoped to its own storefront.
    $order = $this->place->handle($this->buyer, $this->listing, $this->address, couponCode: 'NOTYOURS');

    expect($order->discount_cents)->toBe(0);
});

it('moves through fulfilment one step at a time', function (): void {
    $order = $this->place->handle($this->buyer, $this->listing, $this->address);
    $advance = app(AdvanceOrder::class);

    // A parcel cannot be posted before it is paid for.
    expect(fn () => $advance->markDispatched($order))->toThrow(RuntimeException::class);

    $advance->markPaid($order);
    expect($order->fresh()->paid_at)->not->toBeNull();

    $advance->markDispatched($order->fresh(), 'DHL', '1234');
    expect($order->fresh()->status)->toBe(Order::STATUS_DISPATCHED)
        ->and($order->fresh()->tracking_number)->toBe('1234');
});

it('puts the stone back on sale when an order is cancelled', function (): void {
    $order = $this->place->handle($this->buyer, $this->listing, $this->address);

    app(AdvanceOrder::class)->cancel($order, 'Buyer changed their mind');

    // Otherwise the seller silently loses the item.
    expect($this->listing->fresh()->status)->toBe(Listing::STATUS_ACTIVE)
        ->and($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
});

it('will not cancel a parcel already posted', function (): void {
    $order = $this->place->handle($this->buyer, $this->listing, $this->address);
    $advance = app(AdvanceOrder::class);

    $advance->markPaid($order);
    $advance->markDispatched($order->fresh());

    // That is a return, which is a different record.
    expect(fn () => $advance->cancel($order->fresh()))->toThrow(RuntimeException::class);
});

it('creates one order for a won auction, however often it runs', function (): void {
    $lot = Listing::factory()->published()->auction()->for($this->seller)->for($this->category)->create([
        'auction_ends_at' => now()->subMinute(),
        'current_bid_cents' => 40_000,
    ]);

    $lot->bids()->create([
        'buyer_id' => $this->buyer->id,
        'amount_cents' => 40_000,
        'currency' => 'USD',
    ]);

    $from = app(OrderFromWin::class);

    $first = $from->forAuction($lot->fresh());
    $second = $from->forAuction($lot->fresh());

    expect($first)->not->toBeNull()
        ->and($first->origin)->toBe(Order::ORIGIN_AUCTION)
        ->and($first->subtotal_cents)->toBe(40_000)
        ->and($second->id)->toBe($first->id)
        ->and(Order::count())->toBe(1);
});

it('does not sell a lot that missed its reserve', function (): void {
    $lot = Listing::factory()->published()->auction()->for($this->seller)->for($this->category)->create([
        'auction_ends_at' => now()->subMinute(),
        'reserve_price_cents' => 90_000,
        'current_bid_cents' => 40_000,
    ]);

    $lot->bids()->create([
        'buyer_id' => $this->buyer->id,
        'amount_cents' => 40_000,
        'currency' => 'USD',
    ]);

    // The reserve is the seller's floor, however high the bidding went.
    expect(app(OrderFromWin::class)->forAuction($lot->fresh()))->toBeNull()
        ->and(Order::count())->toBe(0);
});

it('lets a buyer see only their own order', function (): void {
    $order = $this->place->handle($this->buyer, $this->listing, $this->address);
    $other = Buyer::factory()->create();

    $this->actingAs($this->buyer, 'buyer')->get(route('buyer.orders.show', $order))->assertOk();

    auth()->guard('buyer')->logout();

    $this->actingAs($other, 'buyer')->get(route('buyer.orders.show', $order))->assertForbidden();
});

it('lets a seller see only their own sales', function (): void {
    $order = $this->place->handle($this->buyer, $this->listing, $this->address);

    $otherAccount = SellerAccount::factory()->create(['approved_at' => now()]);
    Seller::factory()->create(['seller_account_id' => $otherAccount->id]);

    $this->actingAs($this->account, 'seller')->get(route('seller.orders.show', $order))->assertOk();

    auth()->guard('seller')->logout();

    $this->actingAs($otherAccount, 'seller')->get(route('seller.orders.show', $order))->assertForbidden();
});
