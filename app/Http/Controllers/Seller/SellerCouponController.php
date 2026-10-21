<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Coupons\SaveCoupon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\CouponRequest;
use App\Models\Coupon;
use App\Models\Seller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A seller's discount codes.
 *
 * The storefront is resolved from the session and every coupon is
 * re-checked against it, so a seller can only ever touch their own.
 */
class SellerCouponController extends Controller
{
    public function __construct(
        private readonly SaveCoupon $saveCoupon,
    ) {}

    public function index(Request $request): View
    {
        $storefront = $this->requireStorefront($request);

        return view('pages.seller.coupons', [
            'account' => $request->user('seller'),
            'coupons' => $storefront->coupons()->latest()->get(),
            'types' => config('verum_offers.coupons.types'),
        ]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        $storefront = $this->requireStorefront($request);

        $active = $storefront->coupons()->where('is_active', true)->count();

        if ($active >= config('verum_offers.coupons.max_active_per_seller')) {
            return back()->with('error', __('You already have as many active codes as we allow.'));
        }

        $this->saveCoupon->handle($storefront, $request->validated());

        return back()->with('status', __('Code created.'));
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $this->authoriseOwnership($request, $coupon);

        $this->saveCoupon->handle($coupon->seller, $request->validated(), $coupon);

        return back()->with('status', __('Code updated.'));
    }

    public function destroy(Request $request, Coupon $coupon): RedirectResponse
    {
        $this->authoriseOwnership($request, $coupon);

        $coupon->delete();

        return back()->with('status', __('Code removed.'));
    }

    private function authoriseOwnership(Request $request, Coupon $coupon): void
    {
        abort_unless(
            $coupon->seller_id === $request->user('seller')?->storefront?->id,
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
