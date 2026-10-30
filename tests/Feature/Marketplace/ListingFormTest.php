<?php

use App\Models\Admin;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Notifications\StaffAlert;
use App\Support\PlatformSettings;
use Illuminate\Support\Facades\Notification;

/**
 * The seller's listing form: what it accepts, and where the two buttons go.
 *
 * Gem details are the trade's own terms or real words — a "2" for a
 * colour or a "22" for a shape is refused with a message that says what a
 * good value looks like. "Save as draft" keeps it private; "Publish" puts
 * it live, or into the approval queue when approval is on.
 */
beforeEach(function (): void {
    PlatformSettings::flush();

    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
});

afterEach(fn () => PlatformSettings::flush());

/** A listing a careful seller would submit. */
function goodListing(array $overrides = []): array
{
    return [
        'category_id' => test()->category->id,
        'title' => '2.14 ct unheated Mogok ruby',
        'listing_type' => Listing::TYPE_FIXED,
        'quantity' => 1,
        'currency' => 'USD',
        'price' => '1850.00',
        'shipping_mode' => Listing::SHIPPING_INHERIT,
        'carat' => '2.14',
        'dimensions' => '8.1x6.4x4.2',
        'shape' => 'Oval',
        'cut' => 'Mixed',
        'color' => 'Pigeon-blood red',
        'clarity' => 'Eye clean',
        'treatment' => 'None detected',
        'origin' => 'Mogok',
        'lab' => 'GRS',
        'report_number' => 'GRS2024-061234',
        ...$overrides,
    ];
}

it('rejects values that are not gem details, saying what is expected', function (string $field, mixed $value, string $message): void {
    $this->actingAs($this->account, 'seller')
        ->post(route('seller.listings.store'), goodListing([$field => $value]))
        ->assertSessionHasErrors([$field => $message]);

    expect(Listing::query()->count())->toBe(0);
})->with([
    'a number as a shape' => ['shape', '22', 'Choose a shape from the list.'],
    'a number as a cut' => ['cut', '2', 'Choose a cutting style from the list.'],
    'a number as a clarity' => ['clarity', '2', 'Choose a clarity grade from the list.'],
    'a number as a colour' => ['color', '2', 'Describe the colour in words, e.g. Royal blue.'],
    'one figure as dimensions' => ['dimensions', '2', 'Write the measurements like 8.1 × 6.4 × 4.2 mm.'],
    'no weight' => ['carat', '0', 'Carat weight must be more than zero.'],
    'too many decimals' => ['carat', '2.145', 'Give the carat weight to two decimal places at most.'],
    'a title with no words' => ['title', '12345', 'Describe the piece in words, e.g. 2.14 ct unheated Mogok ruby.'],
    'an unknown lab' => ['lab', 'My cousin', 'Choose a laboratory from the list.'],
    'a free price' => ['price', '0', 'The price field must be at least 0.01.'],
]);

it('refuses an auto-accept above the asking price', function (): void {
    $this->actingAs($this->account, 'seller')
        ->post(route('seller.listings.store'), goodListing([
            'accepts_offers' => '1',
            'auto_accept' => '2000',
        ]))
        ->assertSessionHasErrors(['auto_accept' => 'Auto-accept cannot be above your asking price.']);
});

it('saves a draft and stores dimensions one way', function (): void {
    $this->actingAs($this->account, 'seller')
        ->post(route('seller.listings.store'), goodListing(['intent' => 'draft']))
        ->assertRedirect();

    $listing = Listing::query()->sole();

    expect($listing->status)->toBe(Listing::STATUS_DRAFT)
        ->and($listing->dimensions)->toBe('8.1 × 6.4 × 4.2 mm')
        ->and($listing->cut)->toBe('Mixed');
});

it('publishes straight from the form and lands where photographs are added', function (): void {
    $response = $this->actingAs($this->account, 'seller')
        ->post(route('seller.listings.store'), goodListing(['intent' => 'publish']));

    $listing = Listing::query()->sole();

    $response->assertRedirect(route('seller.listings.edit', $listing));

    expect($listing->isPubliclyVisible())->toBeTrue();
});

it('sends a published listing to the approval queue when approval is on, and alerts admins', function (): void {
    PlatformSettings::put(['listing_approval_required' => true]);
    $admin = Admin::factory()->create();
    Notification::fake();

    $this->actingAs($this->account, 'seller')
        ->post(route('seller.listings.store'), goodListing(['intent' => 'publish']))
        ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'waiting for approval'));

    $listing = Listing::query()->sole();

    expect($listing->isAwaitingApproval())->toBeTrue()
        ->and($listing->isPubliclyVisible())->toBeFalse();

    Notification::assertSentTo($admin, StaffAlert::class);
});

it('offers both buttons on a draft and only save on a live listing', function (): void {
    $this->actingAs($this->account, 'seller')
        ->get(route('seller.listings.create'))
        ->assertOk()
        ->assertSee('Save as draft')
        ->assertSee('value="publish"', false);

    $live = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    $this->actingAs($this->account, 'seller')
        ->get(route('seller.listings.edit', $live))
        ->assertOk()
        ->assertSee('Save changes')
        ->assertDontSee('value="publish"', false);
});

it('names the publish button after the approval switch, in a region that follows it live', function (): void {
    $form = fn () => $this->actingAs($this->account, 'seller')->get(route('seller.listings.create'));

    $form()->assertOk()
        // The region a Settings save re-reads (live-settings.js).
        ->assertSee('data-live-region="listing-publish" data-settings-section="listings"', false)
        ->assertDontSee('Submit for approval');

    PlatformSettings::put(['listing_approval_required' => true]);

    $form()->assertSee('Submit for approval');
});
