<?php

use App\Models\Category;
use App\Models\GemType;
use App\Models\Listing;
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
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
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

/*
 * /shop reads `listings`, not the seeded `stones` catalogue, so these
 * build listings. The rendered HTML still has to carry the content: the
 * grid is a Livewire component, but it renders server-side on first load.
 */
it('renders the shop page with listings', function (): void {
    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create([
        'title' => 'Listed Ruby',
        'price_cents' => 68_000,
    ]);

    $this->get(route('shop'))
        ->assertOk()
        ->assertSee($listing->title)
        ->assertSee('$680');
});

it('filters the shop by branch', function (): void {
    $jewellery = Category::factory()->create(['kind' => Category::KIND_JEWELLERY]);

    Listing::factory()->published()->for($this->seller)->for($this->category)
        ->create(['title' => 'Loose Ruby']);
    Listing::factory()->published()->for($this->seller)->for($jewellery)
        ->create(['title' => 'Ruby Pendant']);

    $this->get(route('shop', ['kind' => Category::KIND_STONE]))
        ->assertOk()
        ->assertSee('Loose Ruby')
        ->assertDontSee('Ruby Pendant');
});

it('rejects an unknown branch filter', function (): void {
    // A branch that does not exist makes the URL meaningless, so it is
    // not found — rather than an empty shop returning 200.
    $this->get(route('shop', ['kind' => 'moonrock']))
        ->assertNotFound();
});

it('filters the shop to untreated stones only', function (): void {
    Listing::factory()->published()->for($this->seller)->for($this->category)
        ->create(['title' => 'Untreated Ruby', 'untreated' => true]);
    Listing::factory()->published()->for($this->seller)->for($this->category)
        ->create(['title' => 'Heated Ruby', 'untreated' => false]);

    $this->get(route('shop', ['untreated' => 1]))
        ->assertOk()
        ->assertSee('Untreated Ruby')
        ->assertDontSee('Heated Ruby');
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

    // A storefront lists `listings`, not the seeded `stones` catalogue.
    Listing::factory()->published()->for($this->seller)->for($this->category)
        ->create(['title' => 'Listed Ruby']);

    $this->get(route('sellers.index'))->assertOk()->assertSee('Test Gem House');

    $this->get(route('sellers.show', $this->seller))
        ->assertOk()
        ->assertSee('Test Gem House')
        ->assertSee('Ada Lovelace')
        ->assertSee('Listed Ruby');
});

it('renders auction lots with the current bid', function (): void {
    // /auctions reads `listings` too; `auction_lots` no longer drives it.
    Listing::factory()->published()->auction()->for($this->seller)->for($this->category)->create([
        'title' => 'Lot Stone',
        'current_bid_cents' => 42_000,
        'bids_count' => 3,
    ]);

    $this->get(route('auctions'))
        ->assertOk()
        ->assertSee('Lot Stone')
        ->assertSee('$420');
});

it('renders the static content pages', function (string $route): void {
    $this->get(route($route))->assertOk();
})->with(['about', 'contact', 'sell', 'help', 'cart', 'checkout']);

it('does not offer WhatsApp across public storefront pages', function (): void {
    $urls = [
        route('home'),
        route('contact'),
        route('sell'),
        route('stones.show', $this->stone),
        route('sellers.show', $this->seller),
    ];

    foreach ($urls as $url) {
        $this->get($url)
            ->assertOk()
            ->assertDontSee('WhatsApp')
            ->assertDontSee('wa.me', escape: false);
    }
});

it('keeps cart and checkout out of the index', function (string $route): void {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('noindex', escape: false);
})->with(['cart', 'checkout']);
