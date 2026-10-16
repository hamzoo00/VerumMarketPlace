<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\SellerAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only account listings for support and moderation.
 */
class AdminAccountController extends Controller
{
    public function buyers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $buyers = Buyer::query()
            ->when(
                $validated['q'] ?? null,
                fn ($query, string $term) => $query
                    ->whereLike('name', "%{$term}%")
                    ->orWhereLike('email', "%{$term}%"),
            )
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $buyers->map(fn (Buyer $buyer): array => [
                'id' => $buyer->id,
                'name' => $buyer->name,
                'email' => $buyer->email,
                'country' => $buyer->country,
                'email_verified' => $buyer->hasVerifiedEmail(),
                'joined' => $buyer->created_at?->toIso8601String(),
            ])->all(),
            'meta' => ['total' => $buyers->total(), 'current_page' => $buyers->currentPage()],
        ]);
    }

    public function sellers(Request $request): JsonResponse
    {
        $accounts = SellerAccount::query()
            ->with('application')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $accounts->map(fn (SellerAccount $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'approved' => $account->isApproved(),
                'approved_at' => $account->approved_at?->toIso8601String(),
                'application_status' => $account->application?->status,
                'joined' => $account->created_at?->toIso8601String(),
            ])->all(),
            'meta' => ['total' => $accounts->total(), 'current_page' => $accounts->currentPage()],
        ]);
    }
}
