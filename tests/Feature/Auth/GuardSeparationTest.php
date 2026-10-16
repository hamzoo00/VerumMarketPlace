<?php

use App\Models\Buyer;
use App\Models\SellerAccount;
use App\Models\SellerApplication;
use Illuminate\Support\Facades\Hash;

/**
 * Buyers and sellers are separate account types. Neither may ever reach the
 * other's area, and an email registered as one must not authenticate as the
 * other.
 */
it('will not let a buyer into the seller area', function (string $route): void {
    $buyer = Buyer::factory()->create();

    $this->actingAs($buyer, 'buyer')
        ->get(route($route))
        ->assertRedirect(route('seller.login'));
})->with(['seller.dashboard', 'seller.status', 'seller.listings']);

it('will not let a seller into the buyer area', function (string $route): void {
    $account = SellerAccount::factory()->approved()->create();

    $this->actingAs($account, 'seller')
        ->get(route($route))
        ->assertRedirect(route('buyer.login'));
})->with(['buyer.dashboard', 'buyer.saved', 'buyer.orders']);

it('will not sign a buyer in through the seller form', function (): void {
    Buyer::factory()->create([
        'email' => 'both@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $this->post(route('seller.login'), [
        'email' => 'both@example.com',
        'password' => 'secret-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('seller');
});

it('will not sign a seller in through the buyer form', function (): void {
    SellerAccount::factory()->approved()->create([
        'email' => 'seller@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $this->post(route('buyer.login'), [
        'email' => 'seller@example.com',
        'password' => 'secret-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('buyer');
});

it('treats the same email in both tables as two unrelated accounts', function (): void {
    $buyer = Buyer::factory()->create([
        'email' => 'same@example.com',
        'password' => Hash::make('buyer-password'),
    ]);
    $seller = SellerAccount::factory()->approved()->create([
        'email' => 'same@example.com',
        'password' => Hash::make('seller-password'),
    ]);

    $this->post(route('buyer.login'), [
        'email' => 'same@example.com',
        'password' => 'buyer-password',
    ])->assertRedirect(route('buyer.dashboard'));

    $this->assertAuthenticatedAs($buyer, 'buyer');
    // Signing in as a buyer must not also authenticate the seller guard.
    $this->assertGuest('seller');
});

it('keeps guest api endpoints separate per guard', function (): void {
    $buyer = Buyer::factory()->create();

    $this->actingAs($buyer, 'buyer')
        ->getJson('/api/seller/me')
        ->assertUnauthorized();
});

it('serves buyer api data to a signed-in buyer', function (): void {
    $buyer = Buyer::factory()->create(['name' => 'Ada Lovelace']);

    $this->actingAs($buyer, 'buyer')
        ->getJson('/api/buyer/me')
        ->assertOk()
        ->assertJson(['name' => 'Ada Lovelace', 'type' => 'buyer']);
});

it('blocks unapproved sellers from approved-only api endpoints', function (): void {
    $account = SellerAccount::factory()->create();
    SellerApplication::factory()->for($account)->create();

    $this->actingAs($account, 'seller')
        ->getJson('/api/seller/listings')
        ->assertForbidden();
});

it('lets an unapproved seller read their own application over the api', function (): void {
    $account = SellerAccount::factory()->create();
    $application = SellerApplication::factory()->for($account)->create();

    $this->actingAs($account, 'seller')
        ->getJson('/api/seller/application')
        ->assertOk()
        ->assertJsonPath('data.reference', $application->reference)
        ->assertJsonPath('data.status', 'submitted');
});

it('serves seller api data once approved', function (): void {
    $account = SellerAccount::factory()->approved()->create();

    $this->actingAs($account, 'seller')
        ->getJson('/api/seller/listings')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['total']]);
});
