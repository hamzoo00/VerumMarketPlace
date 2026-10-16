<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where a seller follows their application through review.
 *
 * Reachable while unapproved; approved sellers are moved along to their
 * dashboard so this never becomes a dead end.
 */
class SellerApplicationStatusController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $account = $request->user('seller');

        if ($account->isApproved()) {
            return redirect()->route('seller.dashboard');
        }

        return view('pages.seller.status', [
            'account' => $account,
            'application' => $account->application,
        ]);
    }
}
