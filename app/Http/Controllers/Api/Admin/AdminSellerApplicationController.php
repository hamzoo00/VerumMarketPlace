<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Sellers\ApproveSellerApplication;
use App\Http\Controllers\Controller;
use App\Models\SellerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Seller vetting from the admin API.
 */
class AdminSellerApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:submitted,in_review,approved,rejected,pending'],
        ]);

        $status = $validated['status'] ?? 'pending';

        $applications = SellerApplication::query()
            ->with('sellerAccount')
            ->when($status === 'pending', fn ($query) => $query->pending())
            ->when($status !== 'pending', fn ($query) => $query->where('status', $status))
            ->orderBy('submitted_at')
            ->paginate(20);

        return response()->json([
            'data' => $applications->map($this->present(...))->all(),
            'meta' => [
                'total' => $applications->total(),
                'per_page' => $applications->perPage(),
                'current_page' => $applications->currentPage(),
            ],
        ]);
    }

    public function show(SellerApplication $application): JsonResponse
    {
        $application->load('sellerAccount');

        return response()->json(['data' => $this->present($application)]);
    }

    public function approve(
        Request $request,
        SellerApplication $application,
        ApproveSellerApplication $approver,
    ): JsonResponse {
        if (! $application->isPending()) {
            return response()->json([
                'message' => __('This application has already been decided.'),
            ], 422);
        }

        $storefront = $approver->handle($application, $request->user('admin')->username);

        return response()->json([
            'message' => __('Application approved.'),
            'data' => [
                ...$this->present($application->fresh()->load('sellerAccount')),
                'storefront_slug' => $storefront->slug,
            ],
        ]);
    }

    public function reject(
        Request $request,
        SellerApplication $application,
        ApproveSellerApplication $approver,
    ): JsonResponse {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if (! $application->isPending()) {
            return response()->json([
                'message' => __('This application has already been decided.'),
            ], 422);
        }

        $approver->reject($application, $validated['reason'], $request->user('admin')->username);

        return response()->json([
            'message' => __('Application rejected.'),
            'data' => $this->present($application->fresh()->load('sellerAccount')),
        ]);
    }

    /** @return array<string, mixed> */
    private function present(SellerApplication $application): array
    {
        return [
            'id' => $application->id,
            'reference' => $application->reference,
            'status' => $application->status,
            'business_name' => $application->business_name,
            'country' => $application->country,
            'trade_license_number' => $application->trade_license_number,
            'specialties' => $application->specialties,
            'about' => $application->about,
            'submitted_at' => $application->submitted_at?->toIso8601String(),
            'reviewed_at' => $application->reviewed_at?->toIso8601String(),
            'reviewed_by' => $application->reviewed_by,
            'decision_note' => $application->decision_note,
            'applicant' => $application->sellerAccount === null ? null : [
                'id' => $application->sellerAccount->id,
                'name' => $application->sellerAccount->name,
                'email' => $application->sellerAccount->email,
                'approved' => $application->sellerAccount->isApproved(),
            ],
        ];
    }
}
