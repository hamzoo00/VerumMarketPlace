<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Sellers\ApproveSellerApplication;
use App\Http\Controllers\Controller;
use App\Models\SellerApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Seller vetting in the browser.
 */
class AdminApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,submitted,in_review,approved,rejected'],
        ]);

        $status = $validated['status'] ?? 'pending';

        return view('pages.admin.applications', [
            'applications' => SellerApplication::query()
                ->with('sellerAccount')
                ->when($status === 'pending', fn ($query) => $query->pending())
                ->when($status !== 'pending', fn ($query) => $query->where('status', $status))
                ->orderBy('submitted_at')
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(SellerApplication $application): View
    {
        return view('pages.admin.application', [
            'application' => $application->load('sellerAccount'),
        ]);
    }

    public function approve(
        Request $request,
        SellerApplication $application,
        ApproveSellerApplication $approver,
    ): RedirectResponse {
        if (! $application->isPending()) {
            return back()->withErrors(['status' => __('This application has already been decided.')]);
        }

        $storefront = $approver->handle($application, $request->user('admin')->username);

        return redirect()
            ->route('admin.applications')
            ->with('status', __('Approved :reference. Storefront is live at /:slug.', [
                'reference' => $application->reference,
                'slug' => config('verum_routes.sellers').'/'.$storefront->slug,
            ]));
    }

    public function reject(
        Request $request,
        SellerApplication $application,
        ApproveSellerApplication $approver,
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if (! $application->isPending()) {
            return back()->withErrors(['status' => __('This application has already been decided.')]);
        }

        $approver->reject($application, $validated['reason'], $request->user('admin')->username);

        return redirect()
            ->route('admin.applications')
            ->with('status', __('Rejected :reference.', ['reference' => $application->reference]));
    }
}
