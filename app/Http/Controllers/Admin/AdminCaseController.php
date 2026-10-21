<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cases\DecideCase;
use App\Http\Controllers\Controller;
use App\Models\SupportCase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The queue of disputes and audits.
 *
 * Every decision goes through DecideCase, so the outcome and the two
 * notifications always happen together.
 */
class AdminCaseController extends Controller
{
    public function __construct(
        private readonly DecideCase $decide,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:all,open,reviewing,resolved'],
            'kind' => ['nullable', 'in:all,dispute,audit'],
        ]);

        $status = $validated['status'] ?? 'open';
        $kind = $validated['kind'] ?? 'all';

        return view('pages.admin.cases', [
            'cases' => SupportCase::query()
                ->with(['buyer', 'seller', 'listing'])
                ->when($status === 'open', fn ($query) => $query->pending())
                ->when(
                    in_array($status, ['reviewing', 'resolved'], true),
                    fn ($query) => $query->where('status', $status),
                )
                ->when($kind !== 'all', fn ($query) => $query->where('kind', $kind))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'kind' => $kind,
            'resolutions' => config('verum_cases.resolutions'),
            'openCount' => SupportCase::query()->pending()->count(),
        ]);
    }

    public function show(SupportCase $case): View
    {
        return view('pages.admin.case', [
            'case' => $case->load(['buyer', 'seller', 'listing.seller', 'resolver']),
            'resolutions' => config('verum_cases.resolutions'),
        ]);
    }

    /** Marks it as being looked at, so two admins do not duplicate work. */
    public function review(SupportCase $case): RedirectResponse
    {
        $this->decide->review($case);

        return back()->with('status', __('Marked as being reviewed.'));
    }

    public function resolve(Request $request, SupportCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'resolution' => ['required', Rule::in(array_keys(config('verum_cases.resolutions')))],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->decide->resolve(
            $case,
            $request->user('admin'),
            $validated['resolution'],
            $validated['note'] ?? null,
        );

        return redirect()->route('admin.cases')
            ->with('status', __('Case :reference settled. Both parties have been told.', [
                'reference' => $case->reference,
            ]));
    }
}
