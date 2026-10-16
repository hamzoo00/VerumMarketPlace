<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Models\SellerApplication;
use App\Models\Stone;
use Illuminate\Http\JsonResponse;

/**
 * Headline counts for the admin dashboard.
 */
class AdminOverviewController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'buyers' => Buyer::count(),
                'seller_accounts' => SellerAccount::count(),
                'sellers_approved' => SellerAccount::whereNotNull('approved_at')->count(),
                'applications_pending' => SellerApplication::pending()->count(),
                'storefronts' => Seller::count(),
                'stones' => Stone::count(),
                'stones_for_sale' => Stone::forSale()->count(),
                'stones_in_auction' => Stone::inAuction()->count(),
            ],
        ]);
    }
}
