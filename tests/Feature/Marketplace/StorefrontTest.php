<?php

use App\Models\AuctionLot;
use App\Models\GemType;
use App\Models\Seller;
use App\Models\SellerReview;
use App\Models\Stone;

/**
 * The storefront must render for guests and put real content in the HTML:
 * these pages are the marketplace's SEO surface.
 */
beforeEach(function (): void {
    $this->gemType = GemType::factory()->create(['slug' => 'ruby', 'label' => 'Ruby', 'plural' => 'Rubies']);
    $this->seller = Seller::factory()->create(['slug' => 'test-house', 'name' => 'Test Gem House']);
    $this->stone = Stone::factory()->for($this->gemType)->for($this->seller)->create([
        'slug' => 'test-ruby',
        'name' => 'Test Ruby',
        'price_cents' => 68_000,
        'report_number' => 'VR-2026-000001',
    ]);
});

it('renders the home page for guests', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Test Ruby')
        ->assertSee($this->stone->report_number);
});

it('renders the shop page with listings', function (): void {
    $this->get(route('shop'))
        ->assertOk()
        ->assertSee('Test Ruby')
        ->assertSee('$680');
});

it('filters the shop by gem type', function (): void {
    $other = GemType::factory()->create(['slug' => 'topaz', 'label' => 'Topaz']);
    Stone::factory()->for($other)->for($this->seller)->create(['name' => 'Golden Topaz']);

    $this->get(route('shop', ['type' => 'ruby']))
        ->assertOk()
        ->assertSee('Test Ruby')
        ->assertDontSee('Golden Topaz');
});

it('rejects an unknown gem type filter', function (): void {
    $this->get(route('shop', ['type' => 'moonrock']))
        ->assertRedirect();
});

it('filters the shop to untreated stones only', function (): void {
    Stone::factory()->for($this->gemType)->for($this->seller)->create([
        'name' => 'Heated Stone',
        'untreated' => false,
    ]);
    $this->stone->update(['untreated' => true]);

    $this->get(route('shop', ['untreated' => 1]))
        ->assertOk()
        ->assertSee('Test Ruby')
        ->assertDontSee('Heated Stone');
});

it('renders a stone detail page with product structured data', function (): void {
    $response = $this->get(route('stones.show', $this->stone))
        ->assertOk()
        ->assertSee('Test Ruby')
        ->assertSee($this->stone->dimensions)
        ->assertSee('Test Gem House');

    expect($response->content())
        ->toContain('"@type":"Product"')
        ->toContain('"price":"680.00"');
});

it('returns 404 for an unknown stone', function (): void {
    $this->get('/stones/does-not-exist')->assertNotFound();
});

it('renders the sellers index and a storefront', function (): void {
    SellerReview::factory()->for($this->seller)->create(['reviewer_name' => 'Ada Lovelace']);

    $this->get(route('sellers.index'))->assertOk()->assertSee('Test Gem House');

    $this->get(route('sellers.show', $this->seller))
        ->assertOk()
        ->assertSee('Test Gem House')
        ->assertSee('Ada Lovelace')
        ->assertSee('Test Ruby');
});

it('renders auction lots with the current bid', function (): void {
    $lotStone = Stone::factory()->for($this->gemType)->for($this->seller)->create([
        'name' => 'Lot Stone',
        'price_cents' => null,
    ]);
    AuctionLot::factory()->for($lotStone)->create([
        'lot_number' => '0199',
        'current_bid_cents' => 42_000,
    ]);

    $this->get(route('auctions'))
        ->assertOk()
        ->assertSee('Lot Stone')
        ->assertSee('$420')
        ->assertSee('0199');
});

it('renders the static content pages', function (string $route): void {
    $this->get(route($route))->assertOk();
})->with(['about', 'contact', 'sell', 'help', 'cart', 'checkout']);

it('keeps cart and checkout out of the index', function (string $route): void {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('noindex', escape: false);
})->with(['cart', 'checkout']);
