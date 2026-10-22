<?php

use App\Http\Middleware\AuthenticateAnyAccount;
use App\Http\Middleware\AuthenticateArea;
use App\Http\Middleware\EnsureAdminIsActive;
use App\Http\Middleware\EnsureSellerIsApproved;
use App\Http\Middleware\RedirectIfAuthenticatedAccount;
use App\Support\DisplayCurrency;
use App\Support\RouteSegments;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * The display-currency cookie is not encrypted.
         *
         * It holds a public ISO country code the visitor chose
         * themselves, so there is nothing to protect, and leaving it
         * readable means the value is the same on the way in as on the
         * way out. Encrypting it would also make it unreadable to
         * anything but PHP, for no gain.
         */
        $middleware->encryptCookies(except: [
            DisplayCurrency::COOKIE_DEFAULT,
        ]);

        $middleware->alias([
            'seller.approved' => EnsureSellerIsApproved::class,
            'admin.active' => EnsureAdminIsActive::class,
            'auth.area' => AuthenticateArea::class,
            'guest.account' => RedirectIfAuthenticatedAccount::class,
            'auth.any' => AuthenticateAnyAccount::class,
        ]);

        // Buyers and sellers are separate guards, so an unauthenticated
        // request goes to the sign-in page for the area it was aimed at.
        // Anything else (the Fortify `web` guard) keeps the default /login.
        // An already-signed-in account hitting a sign-in page goes to its own
        // area. Without this every guard falls back to Fortify's /dashboard.
        $middleware->redirectUsersTo(function (Request $request): string {
            return match (true) {
                Auth::guard('admin')->check() => route('admin.dashboard'),
                Auth::guard('seller')->check() => Auth::guard('seller')->user()->isApproved()
                    ? route('seller.dashboard')
                    : route('seller.status'),
                Auth::guard('buyer')->check() => route('buyer.dashboard'),
                default => route('dashboard'),
            };
        });

        $middleware->redirectGuestsTo(function (Request $request): ?string {
            if ($request->expectsJson()) {
                return null;
            }

            $admin = RouteSegments::get('admin');
            $buyer = RouteSegments::get('buyer');
            $seller = RouteSegments::get('seller');

            // A buyer-only action can live on a public path — bidding and
            // reviewing sit under /item/... — so the route's own guard
            // decides first. Falling back to the path alone would send a
            // buyer to Fortify's /login, where they cannot sign in.
            $guard = collect($request->route()?->gatherMiddleware() ?? [])
                ->map(fn (mixed $rule): string => is_string($rule) ? $rule : '')
                ->first(fn (string $rule): bool => str_starts_with($rule, 'auth:'));

            $named = match ($guard) {
                'auth:admin' => 'admin.login',
                'auth:seller' => 'seller.login',
                'auth:buyer' => 'buyer.login',
                default => null,
            };

            if ($named !== null) {
                return route($named);
            }

            return match (true) {
                $request->is($admin, "{$admin}/*", 'api/admin/*') => route('admin.login'),
                $request->is($seller, "{$seller}/*", 'api/seller/*') => route('seller.login'),
                $request->is($buyer, "{$buyer}/*", 'api/buyer/*') => route('buyer.login'),
                default => route('login'),
            };
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
