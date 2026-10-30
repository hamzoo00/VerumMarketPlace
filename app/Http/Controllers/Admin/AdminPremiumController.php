<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Notices\IssueNotice;
use App\Actions\Premium\DecidePremium;
use App\Http\Controllers\Concerns\RespondsToActions;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Support\PremiumStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Premium standing and the certificates that go with it.
 *
 * Both are granted and withdrawn here, through DecidePremium, so the
 * rules about what revoking costs a seller live in one place rather
 * than in whichever button was clicked.
 */
class AdminPremiumController extends Controller
{
    use RespondsToActions;

    public function __construct(private readonly DecidePremium $decide) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'filter' => ['nullable', Rule::in(['premium', 'certified', 'all'])],
        ]);

        $term = $validated['q'] ?? null;
        $filter = $validated['filter'] ?? 'premium';

        return view('pages.admin.premium', [
            'sellers' => Seller::query()
                ->when($term, fn ($query, string $t) => $query->whereLike('name', "%{$t}%"))
                ->when($filter === 'premium', fn ($query) => $query->whereNotNull('premium_since'))
                ->when($filter === 'certified', fn ($query) => $query->whereNotNull('certified_at'))
                ->withCount('listings')
                ->orderByDesc('premium_since')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'term' => $term,
            'filter' => $filter,
            'counts' => [
                'premium' => Seller::query()->whereNotNull('premium_since')->count(),
                'certified' => Seller::query()->whereNotNull('certified_at')->count(),
            ],
        ]);
    }

    public function update(Request $request, Seller $seller): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['grant', 'revoke', 'certify', 'decertify'])],
            'months' => ['nullable', 'integer', 'min:1', 'max:120'],
            // Printed on the certificate itself, not sent to anyone.
            'note' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:'.IssueNotice::MAX_LENGTH],
        ]);

        $admin = $request->user('admin');
        $message = $validated['message'] ?? null;

        $done = match ($validated['action']) {
            'grant' => tap(__('Premium granted.'), fn () => $this->decide->grant(
                $seller,
                $validated['months'] ?? null,
                $admin,
                $message,
            )),
            // Says plainly what else it costs them, because featured
            // listings come down with it.
            'revoke' => tap(__('Premium revoked; featured listings removed.'), fn () => $this->decide->revoke($seller, $admin, $message)),
            'certify' => tap(__('Certificate issued.'), fn () => $this->decide->certify(
                $seller,
                $admin,
                $validated['note'] ?? null,
                $message,
            )),
            default => tap(__('Certificate withdrawn.'), fn () => $this->decide->decertify($seller, $admin, $message)),
        };

        return $this->done($request, $done);
    }

    /** The certificate as a buyer will see it, before issuing one. */
    public function preview(Seller $seller): View
    {
        abort_unless(PremiumStatus::isCertified($seller), 404);

        return view('pages.admin.certificate', ['seller' => $seller]);
    }
}
