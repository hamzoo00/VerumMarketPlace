<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SupportCase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cases raised about this seller.
 *
 * Read-only: the admin settles them. The seller sees what was raised and
 * what was decided, because being judged without being told is worse
 * than the decision itself.
 */
class SellerCaseController extends Controller
{
    public function index(Request $request): View
    {
        $storefront = $this->requireStorefront($request);

        return view('pages.seller.cases', [
            'account' => $request->user('seller'),
            'cases' => SupportCase::query()
                ->where('seller_id', $storefront->id)
                ->with(['listing', 'buyer'])
                ->latest()
                ->paginate(20),
        ]);
    }

    private function requireStorefront(Request $request): Seller
    {
        $storefront = $request->user('seller')?->storefront;

        abort_if($storefront === null, Response::HTTP_FORBIDDEN, __('Your storefront is not set up yet.'));

        return $storefront;
    }
}
