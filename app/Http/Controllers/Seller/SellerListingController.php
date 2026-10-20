<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Listings\SaveListing;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ListingRequest;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Seller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A seller's own inventory.
 *
 * Every method resolves the storefront from the signed-in account, so a
 * seller can only ever reach their own listings.
 */
class SellerListingController extends Controller
{
    public function __construct(
        private readonly SaveListing $saveListing,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:all,draft,active,paused,sold,ended'],
        ]);

        $status = $validated['status'] ?? 'all';
        $storefront = $this->storefront($request);

        $listings = Listing::query()
            ->with('category')
            ->where('seller_id', $storefront === null ? 0 : $storefront->id)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pages.seller.listings', [
            'account' => $request->user('seller'),
            'storefront' => $storefront,
            'listings' => $listings,
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        return view('pages.seller.listing-form', [
            'account' => $request->user('seller'),
            'listing' => new Listing(['listing_type' => Listing::TYPE_FIXED, 'quantity' => 1]),
            'kinds' => $this->kindsFor($request),
            'action' => route('seller.listings.store'),
            'method' => 'POST',
        ]);
    }

    public function store(ListingRequest $request): RedirectResponse
    {
        $storefront = $this->requireStorefront($request);

        $listing = $this->saveListing->create($storefront, $request->validated());

        return redirect()
            ->route('seller.listings.edit', $listing)
            ->with('status', __('Draft saved. Review it, then publish when you are ready.'));
    }

    public function edit(Request $request, Listing $listing): View
    {
        $this->authoriseOwnership($request, $listing);

        return view('pages.seller.listing-form', [
            'account' => $request->user('seller'),
            'listing' => $listing->load('category'),
            'kinds' => $this->kindsFor($request),
            'action' => route('seller.listings.update', $listing),
            'method' => 'PUT',
        ]);
    }

    public function update(ListingRequest $request, Listing $listing): RedirectResponse
    {
        $this->authoriseOwnership($request, $listing);

        $this->saveListing->update($listing, $request->validated());

        return back()->with('status', __('Listing updated.'));
    }

    public function publish(Request $request, Listing $listing): RedirectResponse
    {
        $this->authoriseOwnership($request, $listing);

        $this->saveListing->publish($listing);

        return redirect()
            ->route('seller.listings')
            ->with('status', __(':title is live.', ['title' => $listing->title]));
    }

    public function destroy(Request $request, Listing $listing): RedirectResponse
    {
        $this->authoriseOwnership($request, $listing);

        $listing->delete();

        return redirect()
            ->route('seller.listings')
            ->with('status', __('Listing removed.'));
    }

    /** Children of a node, for the category browser. */
    public function categories(Request $request, string $kind): JsonResponse
    {
        abort_unless(in_array($kind, [Category::KIND_STONE, Category::KIND_JEWELLERY], true), 404);

        $validated = $request->validate([
            'parent' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $categories = Category::query()
            ->active()
            ->ofKind($kind)
            ->when(
                $validated['parent'] ?? null,
                fn ($query, int $parent) => $query->where('parent_id', $parent),
                fn ($query) => $query->roots(),
            )
            ->withCount('children')
            ->orderBy('position')
            ->get();

        return response()->json([
            'data' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'has_children' => $category->children_count > 0,
            ])->all(),
        ]);
    }

    /**
     * Which branches this seller may list in, from their application.
     *
     * @return list<string>
     */
    private function kindsFor(Request $request): array
    {
        $category = $request->user('seller')?->application?->category;

        return match ($category) {
            'stones' => [Category::KIND_STONE],
            'jewellery' => [Category::KIND_JEWELLERY],
            default => [Category::KIND_STONE, Category::KIND_JEWELLERY],
        };
    }

    private function storefront(Request $request): ?Seller
    {
        return $request->user('seller')?->storefront;
    }

    private function requireStorefront(Request $request): Seller
    {
        $storefront = $this->storefront($request);

        abort_if($storefront === null, Response::HTTP_FORBIDDEN, __('Your storefront is not set up yet.'));

        return $storefront;
    }

    private function authoriseOwnership(Request $request, Listing $listing): void
    {
        abort_unless(
            $listing->seller_id === $this->storefront($request)?->id,
            Response::HTTP_FORBIDDEN,
        );
    }
}
