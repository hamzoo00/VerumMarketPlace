<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Sellers\DecideSellerApplication;
use App\Http\Controllers\Controller;
use App\Models\BlockedEmail;
use App\Models\SellerApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Seller vetting in the browser.
 *
 * Every decision goes through DecideSellerApplication, so the web panel, the
 * API and the CLI cannot drift apart.
 */
class AdminApplicationController extends Controller
{
    public function __construct(
        private readonly DecideSellerApplication $decisions,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,on_hold,archived'],
        ]);

        $status = $validated['status'] ?? 'pending';

        $applications = SellerApplication::query()
            ->with('sellerAccount')
            ->tap(fn ($query) => match ($status) {
                'pending' => $query->pending(),
                'archived' => $query->archived(),
                default => $query->where('status', $status)->whereNull('archived_at'),
            })
            ->orderBy('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.applications', [
            'applications' => $applications,
            'status' => $status,
            'blockedEmails' => $this->blockedLookup($applications->getCollection()),
        ]);
    }

    public function show(SellerApplication $application): View
    {
        $application->load('sellerAccount');

        return view('pages.admin.application', [
            'application' => $application,
            'blocked' => $this->decisions->isBlocked($application),
        ]);
    }

    public function approve(Request $request, SellerApplication $application): RedirectResponse
    {
        if (! $application->isPending()) {
            return back()->withErrors(['status' => __('This application has already been decided.')]);
        }

        $storefront = $this->decisions->approve($application, $this->actor($request));

        return redirect()
            ->route('admin.applications')
            ->with('status', __('Approved :reference. Storefront is live at /:path.', [
                'reference' => $application->reference,
                'path' => config('verum_routes.sellers').'/'.$storefront->slug,
            ]));
    }

    public function reject(Request $request, SellerApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if (! $application->isPending()) {
            return back()->withErrors(['status' => __('This application has already been decided.')]);
        }

        $this->decisions->reject($application, $validated['reason'], $this->actor($request));

        return redirect()
            ->route('admin.applications')
            ->with('status', __('Rejected :reference. That email can no longer apply as a seller.', [
                'reference' => $application->reference,
            ]));
    }

    public function hold(Request $request, SellerApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if (! $application->isPending()) {
            return back()->withErrors(['status' => __('This application has already been decided.')]);
        }

        $this->decisions->hold($application, $validated['note'], $this->actor($request));

        return redirect()
            ->route('admin.applications')
            ->with('status', __('Put :reference on hold and emailed the applicant.', [
                'reference' => $application->reference,
            ]));
    }

    public function archive(SellerApplication $application): RedirectResponse
    {
        $this->decisions->archive($application);

        return back()->with('status', __('Archived :reference.', ['reference' => $application->reference]));
    }

    public function unarchive(SellerApplication $application): RedirectResponse
    {
        $this->decisions->unarchive($application);

        return back()->with('status', __('Restored :reference to the queue.', ['reference' => $application->reference]));
    }

    public function unblock(SellerApplication $application): RedirectResponse
    {
        $this->decisions->unblock($application);

        return back()->with('status', __('That email can apply again.'));
    }

    public function destroy(SellerApplication $application): RedirectResponse
    {
        $reference = $application->reference;
        $this->decisions->delete($application);

        return redirect()
            ->route('admin.applications')
            ->with('status', __('Deleted :reference.', ['reference' => $reference]));
    }

    /**
     * Which of these applicants are blocked, in one query.
     *
     * @param  Collection<int, SellerApplication>  $applications
     * @return array<string, true>
     */
    private function blockedLookup(Collection $applications): array
    {
        $emails = $applications
            ->map(fn (SellerApplication $a): ?string => $a->sellerAccount?->email)
            ->filter()
            ->map(fn (string $email): string => mb_strtolower($email))
            ->unique()
            ->all();

        if ($emails === []) {
            return [];
        }

        $blocked = [];

        foreach (BlockedEmail::query()
            ->whereIn('email', $emails)
            ->where('scope', BlockedEmail::SCOPE_SELLER)
            ->pluck('email') as $email) {
            $blocked[(string) $email] = true;
        }

        return $blocked;
    }

    private function actor(Request $request): string
    {
        return (string) $request->user('admin')?->username;
    }
}
