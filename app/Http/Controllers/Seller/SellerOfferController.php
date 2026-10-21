<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Offers\RespondToOffer;
use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Seller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Offers waiting on the seller.
 *
 * Every offer is re-checked against the signed-in storefront, so one
 * seller can never answer another's negotiation.
 */
class SellerOfferController extends Controller
{
    public function __construct(
        private readonly RespondToOffer $respond,
    ) {}

    public function index(Request $request): View
    {
        $storefront = $this->requireStorefront($request);

        return view('pages.seller.offers', [
            'account' => $request->user('seller'),
            'storefront' => $storefront,
            'offers' => Offer::query()
                ->where('seller_id', $storefront->id)
                ->with(['listing', 'buyer'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function respond(Request $request, Offer $offer): RedirectResponse
    {
        $this->authoriseOwnership($request, $offer);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'decline', 'counter'])],
            'amount' => ['nullable', 'required_if:decision,counter', 'numeric', 'min:0.01'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        match ($validated['decision']) {
            'accept' => $this->respond->accept($offer),
            'decline' => $this->respond->decline($offer),
            default => $this->respond->counter(
                $offer,
                (int) round((float) $validated['amount'] * 100),
                $validated['message'] ?? null,
            ),
        };

        return back()->with('status', __('Offer answered.'));
    }

    private function authoriseOwnership(Request $request, Offer $offer): void
    {
        abort_unless(
            $offer->seller_id === $request->user('seller')?->storefront?->id,
            Response::HTTP_FORBIDDEN,
        );
    }

    private function requireStorefront(Request $request): Seller
    {
        $storefront = $request->user('seller')?->storefront;

        abort_if($storefront === null, Response::HTTP_FORBIDDEN, __('Your storefront is not set up yet.'));

        return $storefront;
    }
}
