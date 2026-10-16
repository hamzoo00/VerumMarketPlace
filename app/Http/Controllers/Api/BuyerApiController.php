<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Buyer endpoints, authenticated by the buyer session guard.
 *
 * Flow and shape only: the collections are empty until orders, bids and saved
 * stones are implemented.
 */
class BuyerApiController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $buyer = $request->user('buyer');

        return response()->json([
            'id' => $buyer->id,
            'name' => $buyer->name,
            'email' => $buyer->email,
            'country' => $buyer->country,
            'type' => 'buyer',
            'email_verified' => $buyer->hasVerifiedEmail(),
            'two_factor_enabled' => $buyer->hasTwoFactorEnabled(),
        ]);
    }

    public function saved(Request $request): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['total' => 0]]);
    }

    public function orders(Request $request): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['total' => 0]]);
    }

    public function bids(Request $request): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['total' => 0]]);
    }
}
