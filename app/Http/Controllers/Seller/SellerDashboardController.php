<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The seller's private area.
 *
 * Every route here sits behind `seller.approved`, so an unapproved account
 * never reaches these methods.
 */
class SellerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.seller.dashboard', [
            'account' => $request->user('seller'),
        ]);
    }

    public function listings(Request $request): View
    {
        $account = $request->user('seller');

        return view('pages.seller.listings', [
            'account' => $account,
            'storefront' => $account->storefront,
        ]);
    }

    public function orders(Request $request): View
    {
        return view('pages.seller.orders', [
            'account' => $request->user('seller'),
        ]);
    }

    public function storefront(Request $request): View
    {
        $account = $request->user('seller');

        return view('pages.seller.storefront', [
            'account' => $account,
            'storefront' => $account->storefront,
        ]);
    }

    public function settings(Request $request): View
    {
        return view('pages.seller.settings', [
            'account' => $request->user('seller'),
        ]);
    }
}
