<?php

use App\Models\Buyer;
use App\Notifications\ResetPassword;
use App\Support\RouteSegments;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

/*
 * Password reset, through the real flow.
 *
 * Buyers and sellers each have their own broker and their own form,
 * named `account.password.*` and carrying an {area}. Fortify's own
 * `password.*` routes serve the `web` guard and are a separate thing.
 * These tests exercise the buyer area; the seller area shares the
 * controller and differs only in the guard.
 */
beforeEach(function (): void {
    $this->area = RouteSegments::get('buyer');
});

it('shows the reset request screen', function (): void {
    $this->get(route('account.password.request', ['area' => $this->area]))
        ->assertOk();
});

it('emails a reset link to a buyer who has an account', function (): void {
    Notification::fake();

    $buyer = Buyer::factory()->create();

    $this->post(route('account.password.email', ['area' => $this->area]), ['email' => $buyer->email])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($buyer, ResetPassword::class);
});

it('says the same thing for an address with no account', function (): void {
    Notification::fake();

    // The wording must not reveal which addresses are registered.
    $this->post(route('account.password.email', ['area' => $this->area]), ['email' => 'nobody@example.com'])
        ->assertSessionHas('status');

    Notification::assertNothingSent();
});

it('renders the form the emailed link points at', function (): void {
    $buyer = Buyer::factory()->create();
    $token = Password::broker('buyers')->createToken($buyer);

    $this->get(route('account.password.reset', [
        'area' => $this->area,
        'token' => $token,
        'email' => $buyer->email,
    ]))->assertOk();
});

it('resets the password with a valid token', function (): void {
    $buyer = Buyer::factory()->create();
    $token = Password::broker('buyers')->createToken($buyer);

    $this->post(route('account.password.update', ['area' => $this->area]), [
        'token' => $token,
        'email' => $buyer->email,
        'password' => 'Reset-Pass1#',
        'password_confirmation' => 'Reset-Pass1#',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('Reset-Pass1#', $buyer->fresh()->password))->toBeTrue();
});

it('refuses a new password that breaks the policy', function (): void {
    $buyer = Buyer::factory()->create();
    $token = Password::broker('buyers')->createToken($buyer);

    $this->post(route('account.password.update', ['area' => $this->area]), [
        'token' => $token,
        'email' => $buyer->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('password');
});
