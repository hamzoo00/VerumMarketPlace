<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Premium\DecideListingReview;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Support\PlatformSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Two queues an admin works through: listings awaiting approval, and
 * premium sellers asking for a feature slot.
 *
 * One controller because the job is the same either way — read a
 * listing, decide, and say why. Every decision goes through
 * DecideListingReview so nothing here can invent a state.
 */
class AdminListingReviewController extends Controller
{
    public function __construct(private readonly DecideListingReview $decide) {}

    public function approvals(Request $request): View
    {
        $validated = $request->validate([
            'state' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $state = $validated['state'] ?? Listing::APPROVAL_PENDING;

        return view('pages.admin.approvals', [
            'listings' => Listing::query()
                ->where('approval_state', $state)
                ->with(['seller', 'category', 'media'])
                ->orderBy('approval_requested_at')
                ->paginate(20)
                ->withQueryString(),
            'state' => $state,
            'pendingCount' => Listing::query()->awaitingApproval()->count(),
            'approvalRequired' => PlatformSettings::approvalRequired(),
        ]);
    }

    public function features(Request $request): View
    {
        $validated = $request->validate([
            'state' => ['nullable', Rule::in(['requested', 'active', 'declined'])],
        ]);

        $state = $validated['state'] ?? Listing::FEATURE_REQUESTED;

        return view('pages.admin.features', [
            'listings' => Listing::query()
                ->where('feature_state', $state)
                ->with(['seller', 'category', 'media'])
                ->orderBy('feature_requested_at')
                ->paginate(20)
                ->withQueryString(),
            'state' => $state,
            'requestedCount' => Listing::query()->awaitingFeature()->count(),
            'activeCount' => Listing::query()->featured()->count(),
            'slots' => PlatformSettings::featuredSlots(),
            'featuringEnabled' => PlatformSettings::featuringEnabled(),
        ]);
    }

    public function decide(Request $request, Listing $listing): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'feature', 'decline', 'unfeature'])],
            // Required only for a rejection: a reason a seller cannot
            // act on is worse than none, because they resubmit as-is.
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'max:200'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $admin = $request->user('admin');

        try {
            $message = match ($validated['action']) {
                'approve' => tap(__('Listing approved.'), fn () => $this->decide->approve($listing, $admin)),
                'reject' => tap(__('Listing rejected.'), fn () => $this->decide->reject(
                    $listing,
                    $admin,
                    (string) $validated['reason'],
                )),
                'feature' => tap(__('Listing featured.'), fn () => $this->decide->feature(
                    $listing,
                    $validated['days'] ?? null,
                )),
                'decline' => tap(__('Feature request declined.'), fn () => $this->decide->declineFeature($listing)),
                default => tap(__('Listing unfeatured.'), fn () => $this->decide->unfeature($listing)),
            };
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $message);
    }
}
