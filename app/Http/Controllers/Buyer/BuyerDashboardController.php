<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The buyer's private area.
 *
 * Shells for now: navigation and layout are real, the panels fill in once
 * orders, bids and saved stones are implemented.
 */
class BuyerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.buyer.dashboard', [
            'buyer' => $request->user('buyer'),
        ]);
    }

    public function saved(Request $request): View
    {
        return view('pages.buyer.saved', [
            'buyer' => $request->user('buyer'),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('pages.buyer.orders', [
            'buyer' => $request->user('buyer'),
        ]);
    }

    public function bids(Request $request): View
    {
        return view('pages.buyer.bids', [
            'buyer' => $request->user('buyer'),
        ]);
    }

    public function settings(Request $request): View
    {
        return view('pages.buyer.settings', [
            'buyer' => $request->user('buyer'),
        ]);
    }
}
