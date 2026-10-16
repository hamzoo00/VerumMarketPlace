<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Seller endpoints, authenticated by the seller session guard.
 *
 * Everything but `me` and `application` additionally requires approval.
 */
class SellerApiController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $account = $request->user('seller');

        return response()->json([
            'id' => $account->id,
            'name' => $account->name,
            'email' => $account->email,
            'country' => $account->country,
            'type' => 'seller',
            'approved' => $account->isApproved(),
            'email_verified' => $account->hasVerifiedEmail(),
            'two_factor_enabled' => $account->hasTwoFactorEnabled(),
        ]);
    }

    public function application(Request $request): JsonResponse
    {
        $application = $request->user('seller')->application;

        if ($application === null) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'reference' => $application->reference,
                'status' => $application->status,
                'business_name' => $application->business_name,
                'submitted_at' => $application->submitted_at?->toIso8601String(),
                'reviewed_at' => $application->reviewed_at?->toIso8601String(),
                'decision_note' => $application->decision_note,
                'timeline' => $application->timeline(),
            ],
        ]);
    }

    public function listings(Request $request): JsonResponse
    {
        $storefront = $request->user('seller')->storefront;

        return response()->json([
            'data' => [],
            'meta' => ['total' => 0, 'storefront' => $storefront?->slug],
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['total' => 0]]);
    }

    public function stats(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'listings' => 0,
                'active_lots' => 0,
                'orders_pending' => 0,
                'revenue_cents' => 0,
            ],
        ]);
    }
}
