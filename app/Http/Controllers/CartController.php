<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Cart and checkout are presentation-only for now; the demo kept its basket in
 * localStorage. Real cart persistence arrives with the backend work.
 */
class CartController extends Controller
{
    public function index(): View
    {
        return view('pages.cart');
    }

    public function checkout(): View
    {
        return view('pages.checkout');
    }
}
