<?php

use App\Http\Controllers\Admin\AdminApplicationController;
use App\Http\Controllers\Admin\AdminCaseController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminListingReviewController;
use App\Http\Controllers\Admin\AdminModerationController;
use App\Http\Controllers\Admin\AdminPremiumController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\AdminSettingsController;
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

        // Flagged messages: suspected attempts to leave the platform.
        Route::get(Seg::get('account.moderation'), [AdminModerationController::class, 'index'])
            ->name('moderation');
        Route::post(Seg::get('account.moderation').'/{message}', [AdminModerationController::class, 'review'])
            ->name('moderation.review');

        // Disputes and audits, in one queue.
        Route::get(Seg::get('account.cases'), [AdminCaseController::class, 'index'])->name('cases');
        Route::get(Seg::get('account.cases').'/{case}', [AdminCaseController::class, 'show'])->name('cases.show');
        Route::post(Seg::get('account.cases').'/{case}/review', [AdminCaseController::class, 'review'])
            ->name('cases.review');
        Route::post(Seg::get('account.cases').'/{case}/resolve', [AdminCaseController::class, 'resolve'])
            ->name('cases.resolve');

        Route::get(Seg::get('account.applications'), [AdminApplicationController::class, 'index'])
            ->name('applications');
        Route::get(Seg::get('account.applications').'/{application}', [AdminApplicationController::class, 'show'])
            ->name('applications.show');
        Route::post(Seg::get('account.applications').'/{application}/approve', [AdminApplicationController::class, 'approve'])
            ->name('applications.approve');
        Route::post(Seg::get('account.applications').'/{application}/reject', [AdminApplicationController::class, 'reject'])
            ->name('applications.reject');
        Route::post(Seg::get('account.applications').'/{application}/hold', [AdminApplicationController::class, 'hold'])
            ->name('applications.hold');
        Route::post(Seg::get('account.applications').'/{application}/archive', [AdminApplicationController::class, 'archive'])
            ->name('applications.archive');
        Route::post(Seg::get('account.applications').'/{application}/unarchive', [AdminApplicationController::class, 'unarchive'])
            ->name('applications.unarchive');
        Route::post(Seg::get('account.applications').'/{application}/unblock', [AdminApplicationController::class, 'unblock'])
            ->name('applications.unblock');
        Route::delete(Seg::get('account.applications').'/{application}', [AdminApplicationController::class, 'destroy'])
            ->name('applications.destroy');

        /*
         * The premium side: what it costs, what gets approved, and who
         * is certified. Each queue is its own page because an admin
         * works one at a time.
         */
        Route::get('settings', [AdminSettingsController::class, 'edit'])->name('settings');
        Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');

        Route::get('approvals', [AdminListingReviewController::class, 'approvals'])->name('approvals');
        Route::get('features', [AdminListingReviewController::class, 'features'])->name('features');
        Route::put('listings/{listing}/review', [AdminListingReviewController::class, 'decide'])
            ->name('listings.review');

        Route::get('premium', [AdminPremiumController::class, 'index'])->name('premium');
        Route::put('premium/{seller}', [AdminPremiumController::class, 'update'])->name('premium.update');
        Route::get('premium/{seller}/certificate', [AdminPremiumController::class, 'preview'])
            ->name('premium.certificate');

        Route::get('buyers', [AdminDashboardController::class, 'buyers'])->name('buyers');
        Route::get('sellers', [AdminDashboardController::class, 'sellers'])->name('sellers');

        Route::post(Seg::get('auth.logout'), [AdminSessionController::class, 'destroy'])->name('logout');
    });
});
