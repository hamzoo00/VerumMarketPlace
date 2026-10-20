<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Contracts\View\View;

/**
 * A single public listing.
 */
class ListingController extends Controller
{
    public function __invoke(Listing $listing): View
    {
        abort_unless($listing->isPublished(), 404);

        $listing->load(['category', 'seller', 'bids.buyer']);

        return view('pages.listing', [
            'listing' => $listing,
            'related' => Listing::query()
                ->published()
                ->with(['category', 'seller', 'media'])
                ->where('category_id', $listing->category_id)
                ->whereKeyNot($listing->getKey())
                ->take(4)
                ->get(),
        ]);
    }
}
