<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * The public catalogue.
 *
 * Filtering, sorting and paging live in the `shop-browser` Livewire
 * component, which reads its state from the query string — so a filtered
 * URL still renders server-side on first request and stays crawlable.
 */
class ShopController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.shop');
    }
}
