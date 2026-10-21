<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Sellers\SaveShippingSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ShippingRequest;
use App\Models\Seller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A seller's postage settings.
 *
 * Both methods resolve the storefront from the signed-in account, so a
 * seller can only ever edit their own rates.
 */
class SellerShippingController extends Controller
{
    public function __construct(
        private readonly SaveShippingSettings $saveSettings,
    ) {}

    public function edit(Request $request): View
    {
        $storefront = $this->requireStorefront($request);

        return view('pages.seller.shipping', [
            'account' => $request->user('seller'),
            'seller' => $storefront,
            'rates' => $storefront->shippingRates()->orderBy('destination')->orderBy('price_cents')->get(),
            'services' => config('verum_shipping.services'),
            'destinations' => config('verum_shipping.destinations'),
        ]);
    }

    public function update(ShippingRequest $request): RedirectResponse
    {
        $this->saveSettings->handle(
            $this->requireStorefront($request),
            $request->validated(),
        );

        return back()->with('status', __('Shipping settings saved.'));
    }

    private function requireStorefront(Request $request): Seller
    {
        $storefront = $request->user('seller')?->storefront;

        abort_if($storefront === null, Response::HTTP_FORBIDDEN, __('Your storefront is not set up yet.'));

        return $storefront;
    }
}
