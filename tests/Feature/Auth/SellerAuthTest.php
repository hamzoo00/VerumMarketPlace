<?php

use App\Models\SellerAccount;
use App\Models\SellerApplication;

it('shows the seller login page to guests', function (): void {
    $this->get(route('seller.login'))->assertOk()->assertSee('Sign in to sell');
});

it('creates an account and a pending application on apply', function (): void {
    $this->post(route('seller.register'), [
        'name' => 'Aung Min',
        'email' => 'mogok@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'business_name' => 'Mogok Valley Gems',
        'country' => 'Myanmar',
    ])->assertRedirect(route('seller.status'));

    $account = SellerAccount::where('email', 'mogok@example.com')->firstOrFail();

    expect($account->isApproved())->toBeFalse()
        ->and($account->application)->not->toBeNull()
        ->and($account->application->status)->toBe(SellerApplication::STATUS_SUBMITTED)
        ->and($account->application->reference)->toStartWith('APP-');

    $this->assertAuthenticatedAs($account, 'seller');
});

it('keeps an unapproved seller out of the seller area', function (string $route): void {
    $account = SellerAccount::factory()->create();
    SellerApplication::factory()->for($account)->create();

    $this->actingAs($account, 'seller')
        ->get(route($route))
        ->assertRedirect(route('seller.status'));
})->with(['seller.dashboard', 'seller.listings', 'seller.orders', 'seller.storefront', 'seller.settings']);

it('lets an unapproved seller watch their application status', function (): void {
    $account = SellerAccount::factory()->create();
    $application = SellerApplication::factory()->for($account)->create();

    $this->actingAs($account, 'seller')
        ->get(route('seller.status'))
        ->assertOk()
        ->assertSee($application->reference)
        ->assertSee('Admin review');
});

it('opens the seller area once an admin approves', function (string $route): void {
    $account = SellerAccount::factory()->approved()->create();
    SellerApplication::factory()->for($account)->approved()->create();

    $this->actingAs($account, 'seller')->get(route($route))->assertOk();
})->with(['seller.dashboard', 'seller.listings', 'seller.orders', 'seller.storefront', 'seller.settings']);

it('moves an approved seller off the status page', function (): void {
    $account = SellerAccount::factory()->approved()->create();
    SellerApplication::factory()->for($account)->approved()->create();

    $this->actingAs($account, 'seller')
        ->get(route('seller.status'))
        ->assertRedirect(route('seller.dashboard'));
});

it('shows a rejected applicant the decision', function (): void {
    $account = SellerAccount::factory()->create();
    SellerApplication::factory()->for($account)->rejected()->create();

    $this->actingAs($account, 'seller')
        ->get(route('seller.status'))
        ->assertOk()
        ->assertSee('Not approved')
        ->assertSee('Trade license could not be verified.');
});

it('signs a seller out', function (): void {
    $account = SellerAccount::factory()->approved()->create();

    $this->actingAs($account, 'seller')
        ->post(route('seller.logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest('seller');
});
