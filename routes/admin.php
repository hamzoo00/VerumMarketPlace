<?php

use App\Http\Controllers\Admin\AdminApplicationController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Support\RouteSegments as Seg;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
| Staff only. There is no public registration: accounts come from
| `php artisan admin:create`. The URL segment is configurable
| (ROUTE_ADMIN) so the sign-in page need not sit at a guessable path.
*/

Route::prefix(Seg::get('admin'))->name('admin.')->group(function () {

    Route::middleware('guest:admin')->group(function () {
        Route::get(Seg::get('auth.login'), [AdminSessionController::class, 'create'])->name('login');
        Route::post(Seg::get('auth.login'), [AdminSessionController::class, 'store'])
            ->middleware('throttle:10,1');
    });

    Route::middleware(['auth:admin', 'admin.active'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get(Seg::get('account.applications'), [AdminApplicationController::class, 'index'])
            ->name('applications');
        Route::get(Seg::get('account.applications').'/{application}', [AdminApplicationController::class, 'show'])
            ->name('applications.show');
        Route::post(Seg::get('account.applications').'/{application}/approve', [AdminApplicationController::class, 'approve'])
            ->name('applications.approve');
        Route::post(Seg::get('account.applications').'/{application}/reject', [AdminApplicationController::class, 'reject'])
            ->name('applications.reject');

        Route::get('buyers', [AdminDashboardController::class, 'buyers'])->name('buyers');
        Route::get('sellers', [AdminDashboardController::class, 'sellers'])->name('sellers');

        Route::post(Seg::get('auth.logout'), [AdminSessionController::class, 'destroy'])->name('logout');
    });
});
