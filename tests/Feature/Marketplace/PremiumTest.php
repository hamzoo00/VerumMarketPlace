<?php

use App\Actions\Listings\SaveListing;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Premium\DecideListingReview;
use App\Actions\Premium\DecidePremium;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Support\PlatformSettings;
use App\Support\PremiumStatus;
use RuntimeException;

/**
 * The premium side: commission, approval, featuring, certificates.
 *
 * What is pinned here is that each switch does nothing until an admin
 * turns it on, that turning one on does not blank what is already live,
 * and that a premium benefit ends when premium does.
 */
beforeEach(function (): void {
    PlatformSettings::flush();

    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
    $this->buyer = Buyer::factory()->create();
    $this->admin = Admin::factory()->create();

    $this->listing = Listing::factory()->for($this->seller)->for($this->category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'status' => Listing::STATUS_DRAFT,
        'price_cents' => 100_000,
    ]);

    $this->premium = app(DecidePremium::class);
    $this->review = app(DecideListingReview::class);
});

afterEach(fn () => PlatformSettings::flush());

it('charges no commission while it is switched off', function (): void {
    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'price_cents' => 100_000,
    ]);

    $order = app(PlaceOrder::class)->handle($this->buyer, $listing, [
        'name' => 'A Buyer', 'line1' => '1 Gem St', 'city' => 'Colombo', 'country' => 'LK',
    ]);

    expect($order->commission_cents)->toBe(0)
        ->and($order->sellerReceives()->cents)->toBe($order->total_cents);
});

it('records the commission and the rate that produced it', function (): void {
    PlatformSettings::put(['commission_enabled' => true, 'commission_percent' => 8]);

    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'price_cents' => 100_000,
    ]);

    $order = app(PlaceOrder::class)->handle($this->buyer, $listing, [
        'name' => 'A Buyer', 'line1' => '1 Gem St', 'city' => 'Colombo', 'country' => 'LK',
    ]);

    // The rate is stored beside the amount: "why is this figure" is
    // asked a month later, when today's rate may differ.
    expect($order->commission_cents)->toBe(8_000)
        ->and((float) $order->commission_rate)->toBe(8.0)
        ->and($order->sellerReceives()->cents)->toBe($order->total_cents - 8_000);
});

it('excludes postage from the commission', function (): void {
    PlatformSettings::put(['commission_enabled' => true, 'commission_percent' => 10]);

    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'price_cents' => 100_000,
        'shipping_mode' => Listing::SHIPPING_CUSTOM,
        'shipping_price_cents' => 5_000,
    ]);

    $order = app(PlaceOrder::class)->handle($this->buyer, $listing, [
        'name' => 'A Buyer', 'line1' => '1 Gem St', 'city' => 'Colombo', 'country' => 'LK',
    ]);

    // 10% of the item alone — the seller pays the carrier out of the
    // postage, so a cut of it would charge them to hand money on.
    expect($order->shipping_cents)->toBe(5_000)
        ->and($order->commission_cents)->toBe(10_000);
});

it('publishes straight away while approval is off', function (): void {
    app(SaveListing::class)->publish($this->listing);

    expect($this->listing->fresh()->approval_state)->toBeNull()
        ->and(Listing::published()->whereKey($this->listing->id)->exists())->toBeTrue();
});

it('queues a listing for approval once the toggle is on', function (): void {
    PlatformSettings::put(['listing_approval_required' => true]);

    app(SaveListing::class)->publish($this->listing);
    $fresh = $this->listing->fresh();

    // Published, but not yet public: one "published" concept, with
    // visibility decided by the approval state.
    expect($fresh->approval_state)->toBe(Listing::APPROVAL_PENDING)
        ->and($fresh->status)->toBe(Listing::STATUS_ACTIVE)
        ->and(Listing::published()->whereKey($fresh->id)->exists())->toBeFalse();

    $this->review->approve($fresh, $this->admin);

    expect(Listing::published()->whereKey($fresh->id)->exists())->toBeTrue();
});

it('leaves listings that predate the rule visible', function (): void {
    $alreadyLive = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    PlatformSettings::put(['listing_approval_required' => true]);

    // Switching approval on must not blank the catalogue.
    expect(Listing::published()->whereKey($alreadyLive->id)->exists())->toBeTrue();
});

it('hides a rejected listing and says why', function (): void {
    PlatformSettings::put(['listing_approval_required' => true]);

    app(SaveListing::class)->publish($this->listing);
    $this->review->reject($this->listing->fresh(), $this->admin, 'Photographs do not match the report.');

    $fresh = $this->listing->fresh();

    expect(Listing::published()->whereKey($fresh->id)->exists())->toBeFalse()
        ->and($fresh->approval_note)->toBe('Photographs do not match the report.');
});

it('refuses a feature request from an ordinary seller', function (): void {
    $live = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    expect(fn () => $this->review->requestFeature($live))->toThrow(RuntimeException::class);
});

it('lets a premium seller ask, and an admin grant', function (): void {
    $this->premium->grant($this->seller);
    $live = Listing::factory()->published()->for($this->seller->fresh())->for($this->category)->create();

    $this->review->requestFeature($live);
    expect($live->fresh()->hasRequestedFeature())->toBeTrue()
        ->and(Listing::featured()->count())->toBe(0);

    $this->review->feature($live->fresh(), 30);

    expect(Listing::featured()->whereKey($live->id)->exists())->toBeTrue();
});

it('drops a featured listing when its run is over', function (): void {
    $this->premium->grant($this->seller);
    $live = Listing::factory()->published()->for($this->seller->fresh())->for($this->category)->create([
        'feature_state' => Listing::FEATURE_ACTIVE,
        'featured_at' => now()->subDays(40),
        'featured_until' => now()->subDay(),
    ]);

    // An expiry in the past ends the slot with no sweep.
    expect(Listing::featured()->whereKey($live->id)->exists())->toBeFalse();
});

it('takes featured listings down when premium ends', function (): void {
    $this->premium->grant($this->seller);
    $live = Listing::factory()->published()->for($this->seller->fresh())->for($this->category)->create();

    $this->review->requestFeature($live);
    $this->review->feature($live->fresh());

    $this->premium->revoke($this->seller->fresh());

    // A slot is a premium benefit; leaving it up sells something the
    // seller no longer has.
    expect(Listing::featured()->count())->toBe(0)
        ->and(PremiumStatus::isPremium($this->seller->fresh()))->toBeFalse();
});

it('will not feature for a seller whose premium lapsed in the queue', function (): void {
    $this->premium->grant($this->seller);
    $live = Listing::factory()->published()->for($this->seller->fresh())->for($this->category)->create();
    $this->review->requestFeature($live);

    $this->premium->revoke($this->seller->fresh());

    expect(fn () => $this->review->feature($live->fresh()))->toThrow(RuntimeException::class);
});

it('issues a certificate and keeps its reference when reissued', function (): void {
    $certified = $this->premium->certify($this->seller, $this->admin, 'Papers checked in person.');
    $reference = $certified->certificate_reference;

    expect($reference)->toStartWith('VRC-')
        ->and(PremiumStatus::isCertified($certified))->toBeTrue();

    $again = $this->premium->certify($certified, $this->admin);

    // A buyer may already have scanned it.
    expect($again->certificate_reference)->toBe($reference);
});

it('keeps the certificate when premium is revoked', function (): void {
    $this->premium->grant($this->seller);
    $this->premium->certify($this->seller->fresh(), $this->admin);

    $after = $this->premium->revoke($this->seller->fresh());

    // It says an admin checked them, which stays true.
    expect(PremiumStatus::isCertified($after))->toBeTrue()
        ->and(PremiumStatus::isPremium($after))->toBeFalse();
});

it('clears the reference when a certificate is withdrawn', function (): void {
    $this->premium->certify($this->seller, $this->admin);
    $after = $this->premium->decertify($this->seller->fresh());

    expect($after->certificate_reference)->toBeNull()
        ->and(PremiumStatus::isCertified($after))->toBeFalse();
});

it('clamps a commission typo rather than storing it', function (): void {
    PlatformSettings::put(['commission_enabled' => true, 'commission_percent' => 900]);

    expect(PlatformSettings::commissionRate())->toBe(50.0);
});
