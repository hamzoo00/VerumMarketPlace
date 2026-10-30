<?php

namespace App\Http\Middleware;

use App\Models\SellerAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a suspended or closing seller from listing or selling.
 *
 * They keep their dashboard — orders already placed must be finished and
 * buyers answered — but the routes that put stock in front of buyers or
 * agree a sale stop here, with the reason, instead of failing deeper in.
 */
class EnsureSellerCanTrade
{
    public function handle(Request $request, Closure $next): Response
    {
        $account = $request->user('seller');

        if (! $account instanceof SellerAccount || $account->canTrade()) {
            return $next($request);
        }

        $message = $account->isSuspended() && ! $account->isClosing()
            ? __('Selling is suspended on your account, so listings cannot be added or changed.')
            : __('Your account is closing, so listings cannot be added or changed.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('seller.dashboard')->with('error', $message);
    }
}
