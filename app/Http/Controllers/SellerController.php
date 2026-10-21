<?php

namespace App\Http\Controllers;

use App\Models\Seller;
use Illuminate\Contracts\View\View;

/**
 * Public seller pages.
 *
 * Reads `listings`, like the rest of the catalogue — `stones` is the
 * seeded demo table and no longer drives anything buyers see.
 */
class SellerController extends Controller
{
    public function index(): View
    {
        return view('pages.sellers.index', [
            'sellers' => Seller::query()
                ->withCount(['listings as listings_published_count' => fn ($query) => $query->published()])
                ->orderByDesc('rating')
                ->get(),
        ]);
    }

    public function show(Seller $seller): View
    {
        $seller->load([
            'reviews' => fn ($query) => $query->orderByDesc('reviewed_on'),
        ]);

        return view('pages.sellers.show', [
            'seller' => $seller,
            'listings' => $seller->listings()
                ->published()
                // `media` is eager-loaded: every card asks for its cover.
                ->with(['category', 'seller', 'media'])
                ->latest('published_at')
                ->get(),
        ]);
    }
}
