<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends already-signed-in accounts away from the guest auth screens.
 *
 * Guard-aware, so a signed-in buyer visiting the seller login is not mistaken
 * for a seller.
 */
class RedirectIfAuthenticatedAccount
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = $guards === [] ? ['buyer', 'seller'] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect()->route(match ($guard) {
                    'seller' => 'seller.dashboard',
                    default => 'buyer.dashboard',
                });
            }
        }

        return $next($request);
    }
}
