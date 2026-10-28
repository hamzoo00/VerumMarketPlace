<?php

namespace App\Http\Controllers;

use App\Models\AuctionLot;
use App\Models\GemType;
use App\Models\Listing;
use App\Models\SellerReview;
use App\Models\Stone;
use App\Support\PlatformSettings;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            'gemTypes' => GemType::orderBy('position')->withCount('stones')->get(),
            // The hero stage cross-fades between these three; hero-<slug>.webp
            // artwork exists only for ruby, sapphire and emerald.
            'heroStones' => Stone::forSale()
                ->with('gemType')
                ->whereIn('slug', ['burmese-ruby', 'royal-blue-sapphire', 'colombian-emerald'])
                ->get()
                ->sortBy(fn (Stone $stone): int => match ($stone->gemType->slug) {
                    'ruby' => 0,
                    'sapphire' => 1,
                    'emerald' => 2,
                    default => 99,
                })
                ->values(),
            'featured' => Stone::forSale()
                ->with(['gemType', 'seller'])
                ->orderByDesc('position')
                ->take(8)
                ->get(),
            /*
             * Paid feature slots, capped by the admin's setting. Kept
             * apart from "Just listed" above, which is simply the newest
             * stones and is not for sale.
             */
            'featuredListings' => Listing::query()
                ->featured()
                ->with(['seller', 'category', 'media'])
                ->orderByDesc('featured_at')
                ->take(PlatformSettings::featuredSlots())
                ->get(),
            'closingLots' => AuctionLot::live()
                ->with(['stone.gemType', 'stone.seller'])
                ->orderBy('ends_at')
                ->take(4)
                ->get(),
            'reviews' => SellerReview::with('seller')
                ->orderByDesc('reviewed_on')
                ->take(6)
                ->get(),
        ]);
    }
}
