<?php

namespace App\Support;

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/**
 * The bell's routes, registered once per area.
 *
 * Called inside each area's group, so the names pick up its prefix
 * (`buyer.notifications`, `seller.notifications`, `admin.notifications`)
 * and its guard middleware. Each declares its audience, exactly like the
 * inbox: the route says whose bell this is, never the guard precedence.
 */
final class NotificationRoutes
{
    public static function register(string $audience): void
    {
        $base = RouteSegments::get('account.notifications');

        Route::get($base, [NotificationController::class, 'index'])
            ->defaults('audience', $audience)
            ->name('notifications');

        Route::get($base.'/bell', [NotificationController::class, 'bell'])
            ->defaults('audience', $audience)
            ->name('notifications.bell');

        Route::post($base.'/read-all', [NotificationController::class, 'readAll'])
            ->defaults('audience', $audience)
            ->name('notifications.read-all');

        Route::post($base.'/{notification}', [NotificationController::class, 'read'])
            ->defaults('audience', $audience)
            ->whereUuid('notification')
            ->name('notifications.read');
    }
}
