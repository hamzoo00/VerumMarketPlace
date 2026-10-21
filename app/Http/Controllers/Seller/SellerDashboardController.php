<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Support\SellerStats;
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
        $account = $request->user('seller');
        $storefront = $account?->storefront;

        return view('pages.seller.dashboard', [
            'account' => $account,
            'storefront' => $storefront,
            'stats' => SellerStats::for($storefront),
            'recent' => $storefront === null ? collect() : Listing::query()
                ->where('seller_id', $storefront->id)
                ->with('category')
                ->latest('updated_at')
                ->take(5)
                ->get(),
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
            'stats' => SellerStats::for($account->storefront),
        ]);
    }

    public function settings(Request $request): View
    {
        return view('pages.seller.settings', [
            'account' => $request->user('seller'),
        ]);
    }
}
