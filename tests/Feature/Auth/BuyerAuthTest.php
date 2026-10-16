<?php

use App\Models\Buyer;
use Illuminate\Support\Facades\Hash;

it('shows the buyer login page to guests', function (): void {
    $this->get(route('buyer.login'))->assertOk()->assertSee('Sign in to buy');
});

it('signs a buyer in and lands them on their dashboard', function (): void {
    $buyer = Buyer::factory()->create([
        'email' => 'buyer@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $this->post(route('buyer.login'), [
        'email' => 'buyer@example.com',
        'password' => 'secret-password',
    ])->assertRedirect(route('buyer.dashboard'));

    $this->assertAuthenticatedAs($buyer, 'buyer');
});

it('rejects a wrong password', function (): void {
    Buyer::factory()->create(['email' => 'buyer@example.com']);

    $this->post(route('buyer.login'), [
        'email' => 'buyer@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('buyer');
});

it('registers a buyer without any approval step', function (): void {
    $this->post(route('buyer.register'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertRedirect(route('buyer.dashboard'));

    $this->assertAuthenticated('buyer');
    expect(Buyer::where('email', 'ada@example.com')->exists())->toBeTrue();
});

it('will not register a duplicate buyer email', function (): void {
    Buyer::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('buyer.register'), [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors('email');
});

it('lets a buyer reach every page of their own area', function (string $route): void {
    $buyer = Buyer::factory()->create();

    $this->actingAs($buyer, 'buyer')->get(route($route))->assertOk();
})->with(['buyer.dashboard', 'buyer.saved', 'buyer.bids', 'buyer.orders', 'buyer.settings']);

it('signs a buyer out', function (): void {
    $buyer = Buyer::factory()->create();

    $this->actingAs($buyer, 'buyer')
        ->post(route('buyer.logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest('buyer');
});

it('sends a signed-in buyer away from the guest auth screens', function (): void {
    $buyer = Buyer::factory()->create();

    $this->actingAs($buyer, 'buyer')
        ->get(route('buyer.login'))
        ->assertRedirect(route('buyer.dashboard'));
});
