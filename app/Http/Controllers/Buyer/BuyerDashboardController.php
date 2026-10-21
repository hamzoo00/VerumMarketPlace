<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Buyer;
use App\Support\BuyerStats;
use App\Support\ReviewEligibility;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The buyer's private area.
 *
 * Counts come from BuyerStats so the tiles and the pages behind them
 * always agree.
 */
class BuyerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $buyer = $this->buyer($request);

        return view('pages.buyer.dashboard', [
            'buyer' => $buyer,
            'stats' => BuyerStats::for($buyer),
            // Prompted here so a buyer does not have to hunt for the
            // listing they bought in order to review it.
            'toReview' => ReviewEligibility::awaitingReview($buyer)->take(3),
            'recentBids' => $this->bidsFor($buyer)->take(5)->get(),
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
        $buyer = $this->buyer($request);

        return view('pages.buyer.bids', [
            'buyer' => $buyer,
            'bids' => $this->bidsFor($buyer)->paginate(20),
            'stats' => BuyerStats::for($buyer),
        ]);
    }

    public function settings(Request $request): View
    {
        return view('pages.buyer.settings', [
            'buyer' => $request->user('buyer'),
        ]);
    }

    /**
     * This buyer's bids, newest first.
     *
     * `listing.seller` is eager-loaded because every row names the lot
     * and its house.
     *
     * @return Builder<Bid>
     */
    private function bidsFor(Buyer $buyer)
    {
        return Bid::query()
            ->where('buyer_id', $buyer->id)
            ->with(['listing.seller'])
            ->latest();
    }

    private function buyer(Request $request): Buyer
    {
        $buyer = $request->user('buyer');

        abort_if($buyer === null, Response::HTTP_FORBIDDEN);

        return $buyer;
    }
}
