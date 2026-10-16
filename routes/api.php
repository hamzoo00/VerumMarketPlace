<?php

use App\Http\Controllers\Api\Admin\AdminAccountController;
use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminOverviewController;
use App\Http\Controllers\Api\Admin\AdminSellerApplicationController;
use App\Http\Controllers\Api\BuyerApiController;
use App\Http\Controllers\Api\SellerApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
| Registered in bootstrap/app.php under the `api` prefix.
|
| Authentication is stateful: these endpoints are called by the site's own
| front-end and authenticate with the same session cookie as the web routes,
| not with bearer tokens. The `web` middleware group is applied so the session
| and CSRF protection are available.
*/

Route::middleware('web')->group(function () {

    // Public: used by the command-palette and storefront widgets.
    Route::get('health', fn () => response()->json(['status' => 'ok']))->name('api.health');

    /*
     * Buyer area.
     */
    Route::middleware('auth:buyer')
        ->prefix('buyer')
        ->name('api.buyer.')
        ->group(function () {
            Route::get('me', [BuyerApiController::class, 'me'])->name('me');
            Route::get('saved', [BuyerApiController::class, 'saved'])->name('saved');
            Route::get('orders', [BuyerApiController::class, 'orders'])->name('orders');
            Route::get('bids', [BuyerApiController::class, 'bids'])->name('bids');
        });

    /*
     * Seller area.
     *
     * `auth:seller` proves who they are; `seller.approved` proves an admin has
     * let them trade. Status is deliberately outside the approval gate so a
     * pending seller can still poll their own application.
     */
    Route::middleware('auth:seller')
        ->prefix('seller')
        ->name('api.seller.')
        ->group(function () {
            Route::get('me', [SellerApiController::class, 'me'])->name('me');
            Route::get('application', [SellerApiController::class, 'application'])->name('application');

            Route::middleware('seller.approved')->group(function () {
                Route::get('listings', [SellerApiController::class, 'listings'])->name('listings');
                Route::get('orders', [SellerApiController::class, 'orders'])->name('orders');
                Route::get('stats', [SellerApiController::class, 'stats'])->name('stats');
            });
        });

    /*
     * Admin area.
     *
     * No public registration reaches this guard; accounts come from
     * `php artisan admin:create`. Every route below `auth:admin` additionally
     * requires the account to still be active.
     */
    Route::prefix('admin')->name('api.admin.')->group(function () {
        Route::post('login', [AdminAuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('login');

        Route::middleware(['auth:admin', 'admin.active'])->group(function () {
            Route::get('me', [AdminAuthController::class, 'me'])->name('me');
            Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

            Route::get('overview', AdminOverviewController::class)->name('overview');

            Route::get('buyers', [AdminAccountController::class, 'buyers'])->name('buyers');
            Route::get('sellers', [AdminAccountController::class, 'sellers'])->name('sellers');

            Route::get('applications', [AdminSellerApplicationController::class, 'index'])->name('applications.index');
            Route::get('applications/{application}', [AdminSellerApplicationController::class, 'show'])->name('applications.show');
            Route::post('applications/{application}/approve', [AdminSellerApplicationController::class, 'approve'])->name('applications.approve');
            Route::post('applications/{application}/reject', [AdminSellerApplicationController::class, 'reject'])->name('applications.reject');
        });
    });
});
