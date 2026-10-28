<?php

use App\Actions\Listings\SaveListing;
use App\Actions\Premium\DecidePremium;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Support\PlatformSettings;

/** Every premium page renders: a Blade slip is invisible to a unit test. */
beforeEach(function (): void {
    PlatformSettings::flush();

    $this->admin = Admin::factory()->create();
    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
});

afterEach(fn () => PlatformSettings::flush());

it('renders every admin premium page', function (string $route): void {
    $this->actingAs($this->admin, 'admin')->get(route($route))->assertOk();
})->with([
    'settings' => 'admin.settings',
    'approvals' => 'admin.approvals',
    'features' => 'admin.features',
    'premium' => 'admin.premium',
]);

it('keeps the premium pages away from everyone else', function (): void {
    $this->get(route('admin.settings'))->assertRedirect();
});

it('saves settings from the form', function (): void {
    $this->actingAs($this->admin, 'admin')
        ->put(route('admin.settings.update'), [
            'commission_enabled' => '1',
            'commission_percent' => '7.5',
            'listing_approval_required' => '1',
            'featured_slots' => '8',
        ])
        ->assertRedirect();

    PlatformSettings::flush();

    expect(PlatformSettings::commissionRate())->toBe(7.5)
        ->and(PlatformSettings::approvalRequired())->toBeTrue()
        // An unchecked box posts nothing, so featuring must read as off.
        ->and(PlatformSettings::featuringEnabled())->toBeFalse();
});

it('shows the certificate card once one is issued', function (): void {
    app(DecidePremium::class)->certify($this->seller, $this->admin, 'Checked in person.');

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.premium.certificate', $this->seller->fresh()))
        ->assertOk()
        ->assertSee('mcert', escape: false)
        ->assertSee($this->seller->fresh()->certificate_reference)
        ->assertSee('Checked in person.');
});

it('404s a certificate preview for an uncertified seller', function (): void {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.premium.certificate', $this->seller))
        ->assertNotFound();
});

it('puts the certificate on the sellers listings', function (): void {
    app(DecidePremium::class)->certify($this->seller, $this->admin);

    $listing = Listing::factory()->published()->for($this->seller->fresh())->for($this->category)->create([
        'carat' => 2.14,
        'origin' => 'Mogok, Myanmar',
        'lab' => 'AGL',
    ]);

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee('mcert', escape: false)
        ->assertSee('Mogok, Myanmar')
        ->assertSee('2.14 ct');
});

it('shows no certificate for an uncertified seller', function (): void {
    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertDontSee('mcert', escape: false);
});

it('decides a listing from the admin queue', function (): void {
    PlatformSettings::put(['listing_approval_required' => true]);

    $listing = Listing::factory()->for($this->seller)->for($this->category)->create([
        'status' => Listing::STATUS_DRAFT,
    ]);

    app(SaveListing::class)->publish($listing);

    $this->actingAs($this->admin, 'admin')
        ->put(route('admin.listings.review', $listing), ['action' => 'approve'])
        ->assertRedirect();

    expect($listing->fresh()->approval_state)->toBe(Listing::APPROVAL_APPROVED);
});

it('requires a reason to reject', function (): void {
    $listing = Listing::factory()->for($this->seller)->for($this->category)->create();

    $this->actingAs($this->admin, 'admin')
        ->put(route('admin.listings.review', $listing), ['action' => 'reject'])
        ->assertSessionHasErrors('reason');
});

it('lets a premium seller ask to feature from their inventory', function (): void {
    app(DecidePremium::class)->grant($this->seller);

    $listing = Listing::factory()->published()->for($this->seller->fresh())->for($this->category)->create();

    $this->actingAs($this->account, 'seller')
        ->post(route('seller.listings.feature', $listing))
        ->assertRedirect();

    expect($listing->fresh()->hasRequestedFeature())->toBeTrue();
});

it('tells an ordinary seller featuring is not for them', function (): void {
    $listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    $this->actingAs($this->account, 'seller')
        ->post(route('seller.listings.feature', $listing))
        ->assertSessionHas('error');

    expect($listing->fresh()->feature_state)->toBeNull();
});
