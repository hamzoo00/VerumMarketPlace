<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Support\SellerStats;

/**
 * The seller's own numbers and the buy actions on a listing.
 *
 * Both regressed once by reading the wrong source — `sellers.listings_count`
 * and the seeded `stones` table — so the counts are pinned here.
 */
beforeEach(function (): void {
    $this->seller = Seller::factory()->create(['listings_count' => 99]);
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
});

it('counts live listings from the listings table, not the stale column', function (): void {
    Listing::factory()->published()->for($this->seller)->for($this->category)->count(2)->create();
    Listing::factory()->for($this->seller)->for($this->category)->create(); // draft

    $stats = SellerStats::for($this->seller);

    expect($stats->live)->toBe(2)
        ->and($stats->drafts)->toBe(1)
        ->and($stats->total)->toBe(3);
});

it('counts only open auctions as active lots', function (): void {
    Listing::factory()->published()->auction()->for($this->seller)->for($this->category)->create([
        'auction_ends_at' => now()->addDay(),
    ]);
    Listing::factory()->published()->auction()->for($this->seller)->for($this->category)->create([
        'auction_ends_at' => now()->subDay(),
    ]);

    expect(SellerStats::for($this->seller)->activeLots)->toBe(1);
});

it('reports zeros for a seller with no storefront', function (): void {
    expect(SellerStats::for(null)->live)->toBe(0);
});

it('offers buy actions to a guest', function (): void {
    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee('Add to cart');
});

it('does not let a seller buy their own listing', function (): void {
    $account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller->update(['seller_account_id' => $account->id]);

    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    $this->actingAs($account, 'seller')
        ->get(route('listings.show', $listing))
        ->assertOk()
        ->assertDontSee('Add to cart')
        ->assertSee('This is your own listing');
});

it('still lets a seller buy another seller listing', function (): void {
    $account = SellerAccount::factory()->create(['approved_at' => now()]);
    Seller::factory()->create(['seller_account_id' => $account->id]);

    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    $this->actingAs($account, 'seller')
        ->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee('Add to cart');
});

it('shows a seller avatar in place of the initials crest', function (): void {
    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    // A real image: the collection checks the MIME type, so a text file
    // named .jpg is rejected — which is the guard working, not a bug.
    $this->seller
        ->addMediaFromBase64(base64_encode(
            (string) file_get_contents(__DIR__.'/../../Fixtures/pixel.png')
        ))
        ->usingFileName('a.png')
        ->toMediaCollection(config('verum_media.avatar.collection'));

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee('crest--photo', escape: false);
});

it('offers an edit link to the owner only', function (): void {
    $account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller->update(['seller_account_id' => $account->id]);

    $this->get(route('sellers.show', $this->seller))
        ->assertOk()
        ->assertDontSee('Edit store');

    $this->actingAs($account, 'seller')
        ->get(route('sellers.show', $this->seller))
        ->assertOk()
        ->assertSee('Edit store');
});

it('puts a save control on every listing card', function (): void {
    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    $this->get(route('shop'))
        ->assertOk()
        ->assertSee('data-wish="'.$listing->slug.'"', escape: false);
});

it('shows a seller listings on their public page', function (): void {
    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create([
        'title' => 'Public Ruby',
    ]);

    Listing::factory()->for($this->seller)->for($this->category)->create([
        'title' => 'Hidden Draft',
    ]);

    $this->get(route('sellers.show', $this->seller))
        ->assertOk()
        ->assertSee($listing->title)
        ->assertDontSee('Hidden Draft')
        ->assertDontSee('No listings right now');
});
