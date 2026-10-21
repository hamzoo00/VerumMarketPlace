<?php

namespace App\Http\Controllers\Buyer;

use App\Actions\Offers\PlaceOffer;
use App\Actions\Offers\RespondToOffer;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Listing;
use App\Models\Offer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A buyer's own negotiations.
 *
 * Every offer is re-checked against the signed-in buyer, so nobody can
 * read or answer someone else's thread by guessing an id.
 */
class BuyerOfferController extends Controller
{
    public function __construct(
        private readonly PlaceOffer $placeOffer,
        private readonly RespondToOffer $respond,
    ) {}

    public function index(Request $request): View
    {
        return view('pages.buyer.offers', [
            'buyer' => $request->user('buyer'),
            'offers' => Offer::query()
                ->where('buyer_id', $this->buyer($request)->id)
                ->with(['listing.seller'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        // PlaceOffer re-checks every rule; this is only the shape.
        $this->placeOffer->handle(
            $listing,
            $this->buyer($request),
            (int) round((float) $validated['amount'] * 100),
            $validated['message'] ?? null,
        );

        return back()->with('status', __('Offer sent. The seller has :hours hours to reply.', [
            'hours' => config('verum_offers.offers.expires_after_hours'),
        ]));
    }

    public function respond(Request $request, Offer $offer): RedirectResponse
    {
        $this->authoriseOwnership($request, $offer);

        $validated = $request->validate([
            'decision' => ['required', 'in:accept,decline,withdraw'],
        ]);

        match ($validated['decision']) {
            'accept' => $this->respond->accept($offer),
            'decline' => $this->respond->decline($offer),
            default => $this->respond->withdraw($offer),
        };

        return back()->with('status', __('Offer updated.'));
    }

    private function authoriseOwnership(Request $request, Offer $offer): void
    {
        abort_unless($offer->buyer_id === $this->buyer($request)->id, Response::HTTP_FORBIDDEN);
    }

    private function buyer(Request $request): Buyer
    {
        $buyer = $request->user('buyer');

        abort_if($buyer === null, Response::HTTP_FORBIDDEN);

        return $buyer;
    }
}
