<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Second gate on the admin area.
 *
 * `auth:admin` proves the session belongs to an admin row; this proves the row
 * is still active, so revoking access does not require deleting the account.
 */
class EnsureAdminIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if ($admin === null || ! $admin->is_active) {
            abort(Response::HTTP_FORBIDDEN, __('This admin account is not active.'));
        }

        return $next($request);
    }
}
