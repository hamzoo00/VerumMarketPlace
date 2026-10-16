<?php

namespace App\Http\Middleware;

use App\Support\RouteSegments;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates against the guard named by the route's {area} parameter.
 *
 * Lets one route serve both account types without either guard leaking into
 * the other's requests.
 */
class AuthenticateArea
{
    public function handle(Request $request, Closure $next): Response
    {
        $area = $request->route('area');
        $guard = is_string($area) ? RouteSegments::guardFor($area) : null;

        if ($guard === null) {
            abort(404);
        }

        if (! Auth::guard($guard)->check()) {
            return $request->expectsJson()
                ? response()->json(['message' => __('Unauthenticated.')], 401)
                : redirect()->guest(route("{$guard}.login"));
        }

        Auth::shouldUse($guard);

        return $next($request);
    }
}
