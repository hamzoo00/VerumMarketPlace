<?php

use App\Models\AuctionLot;
use App\Models\GemType;
use App\Models\Seller;
use App\Models\Stone;

beforeEach(function (): void {
    $this->gemType = GemType::factory()->create(['slug' => 'ruby', 'label' => 'Ruby']);
    $this->seller = Seller::factory()->create(['slug' => 'mogok', 'name' => 'Mogok Valley Gems']);
    $this->stone = Stone::factory()->for($this->gemType)->for($this->seller)->create([
        'slug' => 'mogok-ruby',
        'name' => 'Mogok Ruby',
        'origin' => 'Mogok',
        'country' => 'Myanmar',
        'price_cents' => 91_000,
        'report_number' => 'VR-2026-002455',
        'untreated' => true,
    ]);
});

it('finds a stone by name', function (): void {
    $this->getJson(route('search', ['q' => 'Mogok']))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Mogok Ruby']);
});

it('finds a stone by report number', function (): void {
    $this->getJson(route('search', ['q' => 'VR-2026-002455']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'Mogok Ruby']);
});

it('reports the current bid for auction lots instead of a price', function (): void {
    $lotStone = Stone::factory()->for($this->gemType)->for($this->seller)->create([
        'name' => 'Auction Ruby',
        'price_cents' => null,
    ]);
    AuctionLot::factory()->for($lotStone)->create(['current_bid_cents' => 30_000]);

    $this->getJson(route('search', ['q' => 'Auction Ruby']))
        ->assertOk()
        ->assertJsonFragment(['price' => 'Bid $300']);
});

it('rejects a query that is too short', function (): void {
    $this->getJson(route('search', ['q' => 'a']))->assertStatus(422);
});

it('shows the verify form with no report', function (): void {
    $this->get(route('verify'))
        ->assertOk()
        ->assertSee('Report number')
        ->assertDontSee('Report verified');
});

it('verifies a known report number', function (): void {
    $this->get(route('verify', ['report' => 'VR-2026-002455']))
        ->assertOk()
        ->assertSee('Report verified')
        ->assertSee('Mogok Ruby');
});

it('reports an unknown report number as not found', function (): void {
    $this->get(route('verify', ['report' => 'VR-0000-000000']))
        ->assertOk()
        ->assertSee('No report found');
});

it('serves robots.txt with an absolute sitemap url', function (): void {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Sitemap: '.url('sitemap.xml'))
        ->assertSee('Disallow: /checkout');
});
