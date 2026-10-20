<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Contracts\View\View;

/**
 * Live and recently closed auction lots.
 */
class AuctionController extends Controller
{
    public function __invoke(): View
    {
        $lots = Listing::query()
            ->published()
            ->auctions()
            ->with(['category', 'seller', 'media'])
            ->orderBy('auction_ends_at')
            ->get();

        return view('pages.auctions', [
            'live' => $lots->filter(fn (Listing $lot): bool => ! $lot->hasEnded())->values(),
            'closed' => $lots->filter(fn (Listing $lot): bool => $lot->hasEnded())->values(),
        ]);
    }
}
