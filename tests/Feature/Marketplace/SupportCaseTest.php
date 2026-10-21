<?php

use App\Actions\Cases\DecideCase;
use App\Actions\Cases\RaiseCase;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Models\SupportCase;
use App\Notifications\CaseDecided;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Disputes and audits.
 *
 * The decision reaches both parties or the feature is worthless, so the
 * notifications are asserted as carefully as the state changes.
 */
beforeEach(function (): void {
    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
    $this->buyer = Buyer::factory()->create();
    $this->admin = Admin::factory()->create();

    $this->listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    $this->raise = app(RaiseCase::class);
    $this->decide = app(DecideCase::class);
});

it('records an audit against the listing and its seller', function (): void {
    $case = $this->raise->audit($this->buyer, $this->listing, 'misdescribed', str_repeat('a', 30));

    expect($case->kind)->toBe(SupportCase::KIND_AUDIT)
        ->and($case->listing_id)->toBe($this->listing->id)
        ->and($case->seller_id)->toBe($this->seller->id)
        ->and($case->reference)->toStartWith('AUD-');
});

it('stops a buyer reporting the same listing twice', function (): void {
    $this->raise->audit($this->buyer, $this->listing, 'misdescribed', str_repeat('a', 30));

    expect(fn () => $this->raise->audit($this->buyer, $this->listing, 'fake', str_repeat('b', 30)))
        ->toThrow(ValidationException::class);
});

it('records a dispute against the seller', function (): void {
    $case = $this->raise->dispute($this->buyer, $this->seller, 'not_received', str_repeat('a', 30));

    expect($case->kind)->toBe(SupportCase::KIND_DISPUTE)
        ->and($case->reference)->toStartWith('DSP-');
});

it('caps how many cases one buyer may have open', function (): void {
    $limit = (int) config('verum_cases.limits.max_open_per_buyer');

    for ($i = 0; $i < $limit; $i++) {
        SupportCase::factory()->create(['buyer_id' => $this->buyer->id]);
    }

    expect(fn () => $this->raise->dispute($this->buyer, $this->seller, 'other', str_repeat('a', 30)))
        ->toThrow(ValidationException::class);
});

it('tells both the buyer and the seller when a case is settled', function (): void {
    Notification::fake();

    $case = $this->raise->audit($this->buyer, $this->listing, 'misdescribed', str_repeat('a', 30));

    $this->decide->resolve($case, $this->admin, 'seller_warned', 'Please clarify the wording.');

    Notification::assertSentTo($this->buyer, CaseDecided::class);
    Notification::assertSentTo($this->account, CaseDecided::class);
});

it('takes the listing down when that is the decision', function (): void {
    Notification::fake();

    $case = $this->raise->audit($this->buyer, $this->listing, 'fake', str_repeat('a', 30));

    $this->decide->resolve($case, $this->admin, 'listing_removed');

    expect($this->listing->fresh()->isPublished())->toBeFalse();
});

it('refuses to settle the same case twice', function (): void {
    Notification::fake();

    $case = $this->raise->audit($this->buyer, $this->listing, 'misdescribed', str_repeat('a', 30));
    $this->decide->resolve($case, $this->admin, 'dismissed');

    expect(fn () => $this->decide->resolve($case->fresh(), $this->admin, 'upheld'))
        ->toThrow(ValidationException::class);
});

it('refuses an outcome we do not recognise', function (): void {
    $case = $this->raise->audit($this->buyer, $this->listing, 'misdescribed', str_repeat('a', 30));

    expect(fn () => $this->decide->resolve($case, $this->admin, 'whatever_i_like'))
        ->toThrow(ValidationException::class);
});

it('stops one buyer reading another buyers case', function (): void {
    $case = $this->raise->dispute($this->buyer, $this->seller, 'other', str_repeat('a', 30));

    $this->actingAs(Buyer::factory()->create(), 'buyer')
        ->get(route('buyer.cases.show', $case))
        ->assertForbidden();
});

it('keeps the admin queue away from sellers and buyers', function (): void {
    $this->actingAs($this->account, 'seller')->get(route('admin.cases'))->assertRedirect();
    $this->actingAs($this->buyer, 'buyer')->get(route('admin.cases'))->assertRedirect();
});

it('shows a seller the cases raised about them', function (): void {
    $case = $this->raise->dispute($this->buyer, $this->seller, 'not_received', str_repeat('a', 30));

    $this->actingAs($this->account, 'seller')
        ->get(route('seller.cases'))
        ->assertOk()
        ->assertSee($case->reference);
});
