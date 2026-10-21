<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets any of the three account types through.
 *
 * Messaging is the one surface all three share. `auth:buyer,seller,admin`
 * would do it, but this also decides *which* sign-in page a guest is sent
 * to — a buyer bounced to the seller login cannot get back.
 */
class AuthenticateAnyAccount
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        foreach (['admin', 'seller', 'buyer'] as $guard) {
            if (Auth::guard($guard)->check()) {
                // Pin it, so downstream user() calls resolve without
                // re-checking each guard in turn.
                Auth::shouldUse($guard);

                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        // A guest here is almost always a buyer: sellers and admins
        // reach their inbox from inside their own area.
        return redirect()->guest(route('buyer.login'));
    }
}
