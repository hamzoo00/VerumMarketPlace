<?php

use App\Http\Controllers\Auth\BuyerAuthController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SellerAuthController;
use App\Http\Controllers\Buyer\BuyerDashboardController;
use App\Http\Controllers\Seller\SellerApplicationStatusController;
use App\Http\Controllers\Seller\SellerDashboardController;
use App\Support\RouteSegments as Seg;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Buyer
|--------------------------------------------------------------------------
| Buyers need no approval; registering signs them straight in.
*/

$buyer = Seg::get('buyer');

Route::middleware('guest.account')->group(function () use ($buyer) {
    Route::get($buyer.'/'.Seg::get('auth.login'), [BuyerAuthController::class, 'showLogin'])->name('buyer.login');
    Route::post($buyer.'/'.Seg::get('auth.login'), [BuyerAuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get($buyer.'/'.Seg::get('auth.register'), [BuyerAuthController::class, 'showRegister'])->name('buyer.register');
    Route::post($buyer.'/'.Seg::get('auth.register'), [BuyerAuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth:buyer')->prefix($buyer)->name('buyer.')->group(function () {
    Route::get(Seg::get('account.dashboard'), [BuyerDashboardController::class, 'index'])->name('dashboard');
    Route::get(Seg::get('account.saved'), [BuyerDashboardController::class, 'saved'])->name('saved');
    Route::get(Seg::get('account.orders'), [BuyerDashboardController::class, 'orders'])->name('orders');
    Route::get(Seg::get('account.bids'), [BuyerDashboardController::class, 'bids'])->name('bids');
    Route::get(Seg::get('account.settings'), [BuyerDashboardController::class, 'settings'])->name('settings');

    Route::post(Seg::get('auth.logout'), [BuyerAuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Seller
|--------------------------------------------------------------------------
| Registering creates a pending application. The account can sign in to watch
| its status, but the seller area stays locked until an admin approves it.
*/

$seller = Seg::get('seller');

Route::middleware('guest.account')->group(function () use ($seller) {
    Route::get($seller.'/'.Seg::get('auth.login'), [SellerAuthController::class, 'showLogin'])->name('seller.login');
    Route::post($seller.'/'.Seg::get('auth.login'), [SellerAuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get($seller.'/'.Seg::get('auth.apply'), [SellerAuthController::class, 'showRegister'])->name('seller.register');
    Route::post($seller.'/'.Seg::get('auth.apply'), [SellerAuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth:seller')->prefix($seller)->name('seller.')->group(function () {
    // Outside the approval gate: a pending seller must be able to see progress.
    Route::get(Seg::get('account.status'), SellerApplicationStatusController::class)->name('status');
    Route::post(Seg::get('auth.logout'), [SellerAuthController::class, 'logout'])->name('logout');

    Route::middleware('seller.approved')->group(function () {
        Route::get(Seg::get('account.dashboard'), [SellerDashboardController::class, 'index'])->name('dashboard');
        Route::get(Seg::get('account.listings'), [SellerDashboardController::class, 'listings'])->name('listings');
        Route::get(Seg::get('account.orders'), [SellerDashboardController::class, 'orders'])->name('orders');
        Route::get(Seg::get('account.storefront'), [SellerDashboardController::class, 'storefront'])->name('storefront');
        Route::get(Seg::get('account.settings'), [SellerDashboardController::class, 'settings'])->name('settings');
    });
});

/*
|--------------------------------------------------------------------------
| Password reset and change
|--------------------------------------------------------------------------
| One pair of routes serves both account areas; {area} picks the guard, its
| password broker and its token table. Reset emails are throttled to three
| sends, then a cooldown.
*/

Route::whereIn('area', [$buyer, $seller])->group(function () {
    Route::middleware('guest.account')->group(function () {
        Route::get('{area}/'.Seg::get('auth.forgot_password'), [PasswordResetController::class, 'request'])
            ->name('password.request');

        Route::post('{area}/'.Seg::get('auth.forgot_password'), [PasswordResetController::class, 'email'])
            ->name('password.email');

        Route::get('{area}/'.Seg::get('auth.reset_password').'/{token}', [PasswordResetController::class, 'reset'])
            ->name('password.reset');

        Route::post('{area}/'.Seg::get('auth.reset_password'), [PasswordResetController::class, 'update'])
            ->name('password.update');
    });

    // Changing a password requires being signed in as that account type.
    Route::post('{area}/'.Seg::get('auth.password'), [PasswordChangeController::class, 'update'])
        ->middleware('auth.area')
        ->name('password.change');
});
