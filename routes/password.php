<?php

use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Support\RouteSegments as Seg;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Password reset and change
|--------------------------------------------------------------------------
| One pair of routes serves both account areas; {area} picks the guard, its
| password broker and its token table. Reset emails are throttled to three
| sends, then a cooldown.
|
| Required from FortifyServiceProvider rather than routes/web.php: Fortify
| registers `password.*` on the `web` guard, and whichever loads last owns
| the name. These must, or route('password.email') loses its {area}.
*/

$buyer = Seg::get('buyer');
$seller = Seg::get('seller');

Route::whereIn('area', [$buyer, $seller])->group(function () {
    Route::middleware('guest.account')->group(function () {
        Route::get('{area}/'.Seg::get('auth.forgot_password'), [PasswordResetController::class, 'request'])
            ->name('account.password.request');

        Route::post('{area}/'.Seg::get('auth.forgot_password'), [PasswordResetController::class, 'email'])
            ->name('account.password.email');

        Route::get('{area}/'.Seg::get('auth.reset_password').'/{token}', [PasswordResetController::class, 'reset'])
            ->name('account.password.reset');

        Route::post('{area}/'.Seg::get('auth.reset_password'), [PasswordResetController::class, 'update'])
            ->name('account.password.update');
    });

    // Changing a password requires being signed in as that account type.
    Route::post('{area}/'.Seg::get('auth.password'), [PasswordChangeController::class, 'update'])
        ->middleware('auth.area')
        ->name('account.password.change');
});
