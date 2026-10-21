<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SellerReview;
use App\Support\ReviewEligibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A buyer reviews a seller they bought from.
 *
 * Eligibility is derived from the record — a won lot or an accepted
 * offer — and re-checked here, so a crafted request cannot leave a
 * review for a purchase that never happened.
 */
class BuyerReviewController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $buyer = $this->buyer($request);

        abort_unless(ReviewEligibility::canReview($listing, $buyer), Response::HTTP_FORBIDDEN);

        $validated = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:10', 'max:1500'],
        ]);

        $seller = $listing->seller;

        abort_if($seller === null, Response::HTTP_FORBIDDEN);

        SellerReview::create([
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'listing_id' => $listing->id,
            'reviewer_name' => $buyer->name,
            'place' => $buyer->country,
            'stars' => $validated['stars'],
            'body' => $validated['body'],
            'stone_name' => $listing->title,
            'reviewed_on' => now(),
            // Earned, not claimed: canReview() already proved the purchase.
            'is_verified' => true,
        ]);

        $this->refreshRating($seller->id);

        return back()->with('status', __('Thank you — your review is live.'));
    }

    /**
     * Recompute the seller's headline rating.
     *
     * Denormalised onto `sellers` because every card and storefront shows
     * it; verified reviews only, so the number means something.
     */
    private function refreshRating(int $sellerId): void
    {
        $verified = SellerReview::query()->where('seller_id', $sellerId)->verified();

        Seller::whereKey($sellerId)->update([
            'rating' => round((float) (clone $verified)->avg('stars'), 1),
            'reviews_count' => (clone $verified)->count(),
        ]);
    }

    private function buyer(Request $request): Buyer
    {
        $buyer = $request->user('buyer');

        abort_if($buyer === null, Response::HTTP_FORBIDDEN);

        return $buyer;
    }
}
