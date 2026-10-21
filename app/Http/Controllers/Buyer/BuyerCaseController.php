<?php

namespace App\Http\Controllers\Buyer;

use App\Actions\Cases\RaiseCase;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SupportCase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Disputes and audits a buyer has raised.
 *
 * Every case is re-checked against the signed-in buyer, so nobody reads
 * someone else's by guessing a reference.
 */
class BuyerCaseController extends Controller
{
    public function __construct(
        private readonly RaiseCase $raise,
    ) {}

    public function index(Request $request): View
    {
        return view('pages.buyer.cases', [
            'buyer' => $request->user('buyer'),
            'cases' => SupportCase::query()
                ->where('buyer_id', $this->buyer($request)->id)
                ->with(['seller', 'listing'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(Request $request, SupportCase $case): View
    {
        abort_unless($case->buyer_id === $this->buyer($request)->id, Response::HTTP_FORBIDDEN);

        return view('pages.buyer.case', [
            'buyer' => $request->user('buyer'),
            'case' => $case->load(['seller', 'listing', 'resolver']),
        ]);
    }

    /** A problem with a seller. */
    public function dispute(Request $request, Seller $seller): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(array_keys(config('verum_cases.disputes.reasons')))],
            'body' => ['required', 'string', 'min:20', 'max:'.config('verum_cases.limits.body_max')],
            'listing' => ['nullable', 'string', 'exists:listings,slug'],
        ]);

        $listing = isset($validated['listing'])
            ? Listing::where('slug', $validated['listing'])->first()
            : null;

        $case = $this->raise->dispute(
            $this->buyer($request),
            $seller,
            $validated['reason'],
            $validated['body'],
            $listing,
        );

        return redirect()->route('buyer.cases')
            ->with('status', __('Dispute :reference raised. We will be in touch.', [
                'reference' => $case->reference,
            ]));
    }

    /** Ask us to audit a listing. */
    public function audit(Request $request, Listing $listing): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(array_keys(config('verum_cases.audits.reasons')))],
            'body' => ['required', 'string', 'min:20', 'max:'.config('verum_cases.limits.body_max')],
        ]);

        $case = $this->raise->audit(
            $this->buyer($request),
            $listing,
            $validated['reason'],
            $validated['body'],
        );

        return back()->with('status', __('Thank you — audit :reference is with our team.', [
            'reference' => $case->reference,
        ]));
    }

    private function buyer(Request $request): Buyer
    {
        $buyer = $request->user('buyer');

        abort_if($buyer === null, Response::HTTP_FORBIDDEN);

        return $buyer;
    }
}
