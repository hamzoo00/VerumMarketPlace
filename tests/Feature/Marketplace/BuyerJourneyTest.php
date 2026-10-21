<?php

use App\Models\Bid;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Seller;
use App\Support\ReviewEligibility;

/**
 * Bidding and reviewing.
 *
 * A rating is only worth something if the reviewer actually bought, so
 * each way in — and each way of faking it — is pinned here.
 */
beforeEach(function (): void {
    $this->seller = Seller::factory()->create();
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
    $this->buyer = Buyer::factory()->create();

    $this->lot = Listing::factory()->published()->auction()->for($this->seller)->for($this->category)->create([
        'start_price_cents' => 10_000,
        'bid_increment_cents' => 1_000,
        'auction_ends_at' => now()->addDay(),
    ]);
});

it('lets a signed-in buyer bid on an open lot', function (): void {
    $this->actingAs($this->buyer, 'buyer')
        ->post(route('listings.bids.store', $this->lot), ['amount' => 100])
        ->assertRedirect();

    expect($this->lot->fresh()->current_bid_cents)->toBe(10_000)
        ->and(Bid::where('buyer_id', $this->buyer->id)->count())->toBe(1);
});

it('turns guests away from bidding', function (): void {
    $this->post(route('listings.bids.store', $this->lot), ['amount' => 100])
        ->assertRedirect(route('buyer.login'));

    expect(Bid::count())->toBe(0);
});

it('refuses a bid below the minimum', function (): void {
    $this->actingAs($this->buyer, 'buyer')
        ->post(route('listings.bids.store', $this->lot), ['amount' => 1])
        ->assertSessionHasErrors();

    expect(Bid::count())->toBe(0);
});

it('does not let a buyer review while the lot is still running', function (): void {
    Bid::factory()->for($this->lot)->for($this->buyer)->create(['amount_cents' => 10_000]);

    expect(ReviewEligibility::canReview($this->lot->fresh(), $this->buyer))->toBeFalse();
});

it('lets the winner review once the lot closes', function (): void {
    Bid::factory()->for($this->lot)->for($this->buyer)->create(['amount_cents' => 10_000]);
    $this->lot->update(['auction_ends_at' => now()->subMinute()]);

    expect(ReviewEligibility::canReview($this->lot->fresh(), $this->buyer))->toBeTrue();
});

it('does not let a losing bidder review', function (): void {
    $winner = Buyer::factory()->create();

    Bid::factory()->for($this->lot)->for($this->buyer)->create(['amount_cents' => 10_000]);
    Bid::factory()->for($this->lot)->for($winner)->create(['amount_cents' => 20_000]);
    $this->lot->update(['auction_ends_at' => now()->subMinute()]);

    expect(ReviewEligibility::canReview($this->lot->fresh(), $this->buyer))->toBeFalse()
        ->and(ReviewEligibility::canReview($this->lot->fresh(), $winner))->toBeTrue();
});

it('lets a buyer review after an accepted offer', function (): void {
    $fixed = Listing::factory()->published()->for($this->seller)->for($this->category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'price_cents' => 20_000,
    ]);

    Offer::create([
        'listing_id' => $fixed->id, 'buyer_id' => $this->buyer->id, 'seller_id' => $this->seller->id,
        'thread_id' => 1, 'round' => 1, 'from' => Offer::FROM_BUYER, 'amount_cents' => 15_000,
        'currency' => 'USD', 'status' => Offer::STATUS_ACCEPTED, 'expires_at' => now()->addDay(),
    ]);

    expect(ReviewEligibility::canReview($fixed->fresh(), $this->buyer))->toBeTrue();
});

it('rejects a review from someone who never bought', function (): void {
    $this->lot->update(['auction_ends_at' => now()->subMinute()]);

    $this->actingAs(Buyer::factory()->create(), 'buyer')
        ->post(route('listings.reviews.store', $this->lot), [
            'stars' => 1, 'body' => 'Never bought this but here is a review.',
        ])
        ->assertForbidden();

    expect($this->seller->fresh()->reviews()->count())->toBe(0);
});

it('records a verified review and recomputes the rating', function (): void {
    Bid::factory()->for($this->lot)->for($this->buyer)->create(['amount_cents' => 10_000]);
    $this->lot->update(['auction_ends_at' => now()->subMinute()]);

    $this->actingAs($this->buyer, 'buyer')
        ->post(route('listings.reviews.store', $this->lot), [
            'stars' => 5, 'body' => 'Exactly as described and well packed.',
        ])
        ->assertRedirect();

    $review = $this->seller->reviews()->first();

    expect($review->is_verified)->toBeTrue()
        ->and($review->buyer_id)->toBe($this->buyer->id)
        ->and($this->seller->fresh()->rating)->toBe(5.0);
});

it('allows only one review per purchase', function (): void {
    Bid::factory()->for($this->lot)->for($this->buyer)->create(['amount_cents' => 10_000]);
    $this->lot->update(['auction_ends_at' => now()->subMinute()]);

    $post = fn () => $this->actingAs($this->buyer, 'buyer')
        ->post(route('listings.reviews.store', $this->lot), [
            'stars' => 5, 'body' => 'A perfectly reasonable review body.',
        ]);

    $post()->assertRedirect();
    $post()->assertForbidden();

    expect($this->seller->fresh()->reviews()->count())->toBe(1);
});
