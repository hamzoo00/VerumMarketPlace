<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Notices\IssueNotice;
use App\Actions\Sellers\DecideSellerStanding;
use App\Http\Controllers\Concerns\RespondsToActions;
use App\Http\Controllers\Controller;
use App\Models\AccountNotice;
use App\Models\SellerAccount;
use App\Support\MessageOffences;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Every seller account, where it stands, and what an admin can do to it.
 *
 * The decisions themselves live in DecideSellerStanding; this only reads
 * the form and answers in whichever shape the caller asked for.
 */
class AdminSellerController extends Controller
{
    use RespondsToActions;

    public function __construct(private readonly DecideSellerStanding $standing) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'standing' => ['nullable', Rule::in(['all', ...array_keys(SellerAccount::standingLabels())])],
        ]);

        $term = $validated['q'] ?? null;
        $standing = $validated['standing'] ?? 'all';

        $accounts = SellerAccount::query()
            ->with(['application', 'storefront'])
            ->when($term, fn ($query, string $t) => $query
                ->where(fn ($inner) => $inner
                    ->whereLike('name', "%{$t}%")
                    ->orWhereLike('email', "%{$t}%")))
            ->when($standing !== 'all', fn ($query) => $this->filterByStanding($query, $standing))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $ids = $accounts->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return view('pages.admin.sellers', [
            'accounts' => $accounts,
            'term' => $term,
            'standing' => $standing,
            // One grouped query each for the page, not one per row.
            'warnings' => AccountNotice::warningCounts(SellerAccount::class, $ids),
            // `messages.sender_id` holds the storefront id for a seller.
            'flags' => MessageOffences::forSellers(
                $accounts->getCollection()
                    ->pluck('storefront.id')
                    ->filter()
                    ->map(fn ($id): int => (int) $id)
                    ->values()
                    ->all(),
            ),
        ]);
    }

    public function update(Request $request, SellerAccount $account): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['warn', 'suspend', 'reinstate', 'close'])],
            'message' => ['nullable', 'string', 'max:'.IssueNotice::MAX_LENGTH],
        ]);

        $admin = $request->user('admin');
        $message = $validated['message'] ?? null;

        $done = match ($validated['action']) {
            'warn' => tap(__('Warning sent to :name.', ['name' => $account->name]), fn () => $this->standing->warn($account, $admin, $message)),
            'suspend' => tap(__('Selling suspended. The storefront is hidden.'), fn () => $this->standing->suspend($account, $admin, $message)),
            'reinstate' => tap(__('Selling reinstated. The storefront is back.'), fn () => $this->standing->reinstate($account, $admin, $message)),
            default => $this->closed($this->standing->close($account, $admin, $message)),
        };

        return $this->done($request, $done);
    }

    /** Says which kind of closure happened, because the admin will be asked. */
    private function closed(SellerAccount $account): string
    {
        return $account->isClosed()
            ? __('Account closed. Nothing was on its way, so it closed at once.')
            : __('Account closing. Open orders keep it until :date.', [
                'date' => $account->closes_at?->format('j M Y'),
            ]);
    }

    /**
     * @param  Builder<SellerAccount>  $query
     */
    private function filterByStanding($query, string $standing): void
    {
        match ($standing) {
            SellerAccount::STANDING_PENDING => $query->whereNull('approved_at'),
            SellerAccount::STANDING_SUSPENDED => $query->whereNotNull('suspended_at')->whereNull('closing_started_at'),
            SellerAccount::STANDING_CLOSING => $query->whereNotNull('closing_started_at')->whereNull('closed_at'),
            SellerAccount::STANDING_CLOSED => $query->whereNotNull('closed_at'),
            default => $query->whereNotNull('approved_at')->whereNull('suspended_at')->whereNull('closing_started_at'),
        };
    }
}
