<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps unapproved sellers out of the seller area.
 *
 * They stay signed in and are sent to their status page, so they can follow
 * the application through review rather than hitting a dead end.
 */
class EnsureSellerIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $account = $request->user('seller');

        if ($account === null) {
            return redirect()->guest(route('seller.login'));
        }

        // A session opened before the account closed ends here; sign-in
        // itself already refuses a closed account.
        if ($account->isClosed()) {
            auth('seller')->logout();

            return redirect()->route('seller.login')
                ->with('error', __('This seller account has been closed.'));
        }

        if (! $account->isApproved()) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => __('Your seller account is awaiting approval.'),
                    'status' => $account->application?->status,
                ], Response::HTTP_FORBIDDEN)
                : redirect()->route('seller.status');
        }

        return $next($request);
    }
}
