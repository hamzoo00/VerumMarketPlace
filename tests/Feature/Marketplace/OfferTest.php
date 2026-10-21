<?php

use App\Actions\Offers\PlaceOffer;
use App\Actions\Offers\RespondToOffer;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Support\OfferRules;
use Illuminate\Validation\ValidationException;

/**
 * Price negotiation.
 *
 * These rules decide what someone pays, so each branch is pinned: the
 * floor, the auto-decisions, the one-live-offer rule, and the ownership
 * boundary between two sellers.
 */
beforeEach(function (): void {
    $this->seller = Seller::factory()->create();
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
    $this->buyer = Buyer::factory()->create();

    $this->listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'price_cents' => 20_000,
        'accepts_offers' => true,
    ]);

    $this->place = app(PlaceOffer::class);
    $this->respond = app(RespondToOffer::class);
});

it('refuses an offer on a listing that does not take them', function (): void {
    $this->listing->update(['accepts_offers' => false]);

    expect(fn () => $this->place->handle($this->listing->fresh(), $this->buyer, 15_000))
        ->toThrow(ValidationException::class);
});

it('refuses an offer below the floor', function (): void {
    // Default floor is half the asking price.
    expect(fn () => $this->place->handle($this->listing, $this->buyer, 5_000))
        ->toThrow(ValidationException::class);
});

it('refuses an offer at or above the asking price', function (): void {
    expect(fn () => $this->place->handle($this->listing, $this->buyer, 20_000))
        ->toThrow(ValidationException::class);
});

it('accepts automatically above the sellers threshold', function (): void {
    $this->listing->update(['auto_accept_cents' => 18_000]);

    $offer = $this->place->handle($this->listing->fresh(), $this->buyer, 19_000);

    expect($offer->status)->toBe(Offer::STATUS_ACCEPTED)
        ->and($offer->responded_at)->not->toBeNull();
});

it('tells the buyer the real floor when the seller sets auto-decline', function (): void {
    $this->listing->update(['auto_decline_cents' => 16_000]);

    // Rejected at the form rather than recorded as a declined offer: the
    // buyer learns the number instead of wasting a round.
    expect(fn () => $this->place->handle($this->listing->fresh(), $this->buyer, 15_500))
        ->toThrow(ValidationException::class);
});

it('declines on arrival if an offer gets past the form', function (): void {
    // verdictFor is the backstop, exercised directly.
    $this->listing->update(['auto_decline_cents' => 16_000]);

    expect(OfferRules::verdictFor($this->listing->fresh(), 15_500))
        ->toBe(Offer::STATUS_DECLINED);
});

it('holds an offer between the thresholds for the seller', function (): void {
    $offer = $this->place->handle($this->listing, $this->buyer, 15_000);

    expect($offer->status)->toBe(Offer::STATUS_PENDING)
        ->and($offer->thread_id)->toBe($offer->id)
        ->and($offer->expires_at->isFuture())->toBeTrue();
});

it('allows only one live offer per buyer on a listing', function (): void {
    $this->place->handle($this->listing, $this->buyer, 15_000);

    expect(fn () => $this->place->handle($this->listing->fresh(), $this->buyer, 16_000))
        ->toThrow(ValidationException::class);
});

it('supersedes the buyer offer when the seller counters', function (): void {
    $offer = $this->place->handle($this->listing, $this->buyer, 15_000);

    $counter = $this->respond->counter($offer, 17_500);

    expect($counter->round)->toBe(2)
        ->and($counter->from)->toBe(Offer::FROM_SELLER)
        ->and($counter->thread_id)->toBe($offer->thread_id)
        ->and($offer->fresh()->status)->toBe(Offer::STATUS_COUNTERED);
});

it('refuses a counter at or above the asking price', function (): void {
    $offer = $this->place->handle($this->listing, $this->buyer, 15_000);

    expect(fn () => $this->respond->counter($offer, 25_000))
        ->toThrow(ValidationException::class);
});

it('refuses to answer an offer that is already settled', function (): void {
    $offer = $this->place->handle($this->listing, $this->buyer, 15_000);
    $this->respond->decline($offer);

    expect(fn () => $this->respond->accept($offer->fresh()))
        ->toThrow(ValidationException::class);
});

it('treats a lapsed offer as closed without a sweep', function (): void {
    $offer = $this->place->handle($this->listing, $this->buyer, 15_000);
    $offer->update(['expires_at' => now()->subMinute()]);

    expect($offer->fresh()->isLive())->toBeFalse()
        ->and(Offer::live()->count())->toBe(0);
});

it('stops one seller answering another sellers offer', function (): void {
    $offer = $this->place->handle($this->listing, $this->buyer, 15_000);

    $intruderAccount = SellerAccount::factory()->create(['approved_at' => now()]);
    Seller::factory()->create(['seller_account_id' => $intruderAccount->id]);

    $this->actingAs($intruderAccount, 'seller')
        ->post(route('seller.offers.respond', $offer), ['decision' => 'accept'])
        ->assertForbidden();

    expect($offer->fresh()->status)->toBe(Offer::STATUS_PENDING);
});

it('stops one buyer touching another buyers offer', function (): void {
    $offer = $this->place->handle($this->listing, $this->buyer, 15_000);

    $this->actingAs(Buyer::factory()->create(), 'buyer')
        ->post(route('buyer.offers.respond', $offer), ['decision' => 'withdraw'])
        ->assertForbidden();

    expect($offer->fresh()->status)->toBe(Offer::STATUS_PENDING);
});
