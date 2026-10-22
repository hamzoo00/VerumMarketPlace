<?php

use App\Actions\Orders\PlaceOrder;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Models\ShippingRate;

/** Every order page renders: a Blade slip is invisible to a unit test. */
beforeEach(function (): void {
    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
    $this->buyer = Buyer::factory()->create();

    $this->listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'price_cents' => 68_000,
    ]);
});

it('renders the checkout form for a buyer', function (): void {
    ShippingRate::factory()->create(['seller_id' => $this->seller->id, 'is_active' => true]);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('listings.checkout', $this->listing))
        ->assertOk()
        ->assertSee($this->listing->title)
        ->assertSee('Place order');
});

it('sends a guest to sign in rather than to checkout', function (): void {
    $this->get(route('listings.checkout', $this->listing))
        ->assertRedirect(route('buyer.login'));
});

it('404s checkout on an auction lot', function (): void {
    $lot = Listing::factory()->published()->auction()->for($this->seller)->for($this->category)->create();

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('listings.checkout', $lot))
        ->assertNotFound();
});

it('places an order through the form and shows it', function (): void {
    $response = $this->actingAs($this->buyer, 'buyer')
        ->post(route('listings.checkout.store', $this->listing), [
            'name' => 'A Buyer',
            'line1' => '1 Gem Street',
            'city' => 'Colombo',
            'country' => 'LK',
        ]);

    $order = Order::firstOrFail();

    $response->assertRedirect(route('buyer.orders.show', $order));

    $this->get(route('buyer.orders.show', $order))
        ->assertOk()
        ->assertSee($order->reference)
        ->assertSee('1 Gem Street');
});

it('rejects a checkout with no address', function (): void {
    $this->actingAs($this->buyer, 'buyer')
        ->post(route('listings.checkout.store', $this->listing), ['country' => 'LK'])
        ->assertSessionHasErrors(['name', 'line1', 'city']);
});

it('renders both order lists and the seller detail', function (): void {
    $order = app(PlaceOrder::class)->handle($this->buyer, $this->listing, [
        'name' => 'A Buyer',
        'line1' => '1 Gem Street',
        'city' => 'Colombo',
        'country' => 'LK',
    ]);

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.orders'))
        ->assertOk()
        ->assertSee($order->reference);

    auth()->guard('buyer')->logout();

    $this->actingAs($this->account, 'seller')
        ->get(route('seller.orders', ['status' => 'all']))
        ->assertOk()
        ->assertSee($order->reference);

    $this->get(route('seller.orders.show', $order))
        ->assertOk()
        ->assertSee('Mark as paid');
});

it('walks an order through fulfilment from the seller panel', function (): void {
    $order = app(PlaceOrder::class)->handle($this->buyer, $this->listing, [
        'name' => 'A Buyer',
        'line1' => '1 Gem Street',
        'city' => 'Colombo',
        'country' => 'LK',
    ]);

    $this->actingAs($this->account, 'seller')
        ->put(route('seller.orders.update', $order), ['action' => 'paid'])
        ->assertRedirect();

    $this->put(route('seller.orders.update', $order), [
        'action' => 'dispatched',
        'tracking_carrier' => 'DHL',
        'tracking_number' => 'ABC123',
    ])->assertRedirect();

    expect($order->fresh()->tracking_number)->toBe('ABC123');

    // Out of turn: already posted, so there is nothing to confirm.
    $this->put(route('seller.orders.update', $order), ['action' => 'paid'])
        ->assertSessionHas('error');
});
