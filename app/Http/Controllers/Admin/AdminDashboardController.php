<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Models\SellerApplication;
use App\Models\Stone;
use App\Support\MessageOffences;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        return view('pages.admin.dashboard', [
            'stats' => [
                'buyers' => Buyer::count(),
                'seller_accounts' => SellerAccount::count(),
                'sellers_approved' => SellerAccount::whereNotNull('approved_at')->count(),
                'applications_pending' => SellerApplication::pending()->count(),
                'storefronts' => Seller::count(),
                'stones' => Stone::count(),
                'stones_for_sale' => Stone::forSale()->count(),
                'stones_in_auction' => Stone::inAuction()->count(),
            ],
            'pending' => SellerApplication::with('sellerAccount')
                ->pending()
                ->orderBy('submitted_at')
                ->take(5)
                ->get(),
        ]);
    }

    public function buyers(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $term = $validated['q'] ?? null;

        return view('pages.admin.buyers', [
            'buyers' => Buyer::query()
                ->when($term, fn ($query, string $t) => $query
                    ->where(fn ($inner) => $inner
                        ->whereLike('name', "%{$t}%")
                        ->orWhereLike('email', "%{$t}%")))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'term' => $term,
        ]);
    }

    public function sellers(): View
    {
        $accounts = SellerAccount::query()
            ->with(['application', 'storefront'])
            ->latest()
            ->paginate(20);

        return view('pages.admin.sellers', [
            'accounts' => $accounts,
            // Upheld moderation flags per storefront, so a seller working
            // to take buyers off the platform is visible from the list
            // without opening the queue. `messages.sender_id` holds the
            // storefront id for a seller, not the account id.
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
}
