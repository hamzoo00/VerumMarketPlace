<?php

namespace App\Http\Controllers\Buyer;

use App\Actions\Auctions\PlaceBid;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bidding on a lot.
 *
 * PlaceBid holds every rule and applies them with the listing row
 * locked; this only turns a request into a call.
 */
class BuyerBidController extends Controller
{
    public function __construct(
        private readonly PlaceBid $placeBid,
    ) {}

    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $this->placeBid->handle(
            $listing,
            $this->buyer($request),
            (int) round((float) $validated['amount'] * 100),
            $request->ip(),
        );

        return back()->with('status', __('Bid placed. We will tell you if you are outbid.'));
    }

    private function buyer(Request $request): Buyer
    {
        $buyer = $request->user('buyer');

        abort_if($buyer === null, Response::HTTP_FORBIDDEN);

        return $buyer;
    }
}
