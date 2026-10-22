<?php

namespace App\Http\Controllers\Buyer;

use App\Actions\Orders\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\PlaceOrderRequest;
use App\Models\Listing;
use App\Models\Order;
use App\Support\ShippingQuote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Buying one listing, and the orders that result.
 *
 * Per listing rather than through a basket: a gem is one of a kind, so a
 * sale here is one stone from one seller, and the postage and dispatch
 * promise belong to that seller alone.
 */
class BuyerOrderController extends Controller
{
    public function __construct(private readonly PlaceOrder $placeOrder) {}

    public function index(Request $request): View
    {
        $buyer = $request->user('buyer');

        return view('pages.buyer.orders', [
            'buyer' => $buyer,
            'orders' => Order::query()
                ->where('buyer_id', $buyer->id)
                ->with(['items', 'seller'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authoriseBuyer($request, $order);

        return view('pages.buyer.order', [
            'buyer' => $request->user('buyer'),
            'order' => $order->load(['items.listing', 'seller']),
        ]);
    }

    /** The delivery form for one listing. */
    public function create(Request $request, Listing $listing): View
    {
        $buyer = $request->user('buyer');

        abort_unless($this->isBuyable($listing), Response::HTTP_NOT_FOUND);

        return view('pages.buyer.checkout', [
            'buyer' => $buyer,
            'listing' => $listing->load('seller'),
            'quote' => ShippingQuote::for($listing),
        ]);
    }

    public function store(PlaceOrderRequest $request, Listing $listing): RedirectResponse
    {
        $buyer = $request->user('buyer');
        $validated = $request->validated();

        try {
            $order = $this->placeOrder->handle(
                buyer: $buyer,
                listing: $listing,
                address: [
                    'name' => $validated['name'],
                    'line1' => $validated['line1'],
                    'line2' => $validated['line2'] ?? null,
                    'city' => $validated['city'],
                    'region' => $validated['region'] ?? null,
                    'postcode' => $validated['postcode'] ?? null,
                    'country' => $validated['country'],
                    'phone' => $validated['phone'] ?? null,
                ],
                shippingService: $validated['shipping_service'] ?? null,
                couponCode: $validated['coupon_code'] ?? null,
                note: $validated['note'] ?? null,
            );
        } catch (RuntimeException $e) {
            // Someone else bought it while this form was open, or the
            // listing changed underneath. Say so plainly.
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('buyer.orders.show', $order)
            ->with('status', __('Order :reference placed.', ['reference' => $order->reference]));
    }

    private function isBuyable(Listing $listing): bool
    {
        return $listing->status === Listing::STATUS_ACTIVE
            && $listing->published_at !== null
            && ! $listing->isAuction();
    }

    private function authoriseBuyer(Request $request, Order $order): void
    {
        abort_unless(
            (int) $order->buyer_id === (int) $request->user('buyer')->id,
            Response::HTTP_FORBIDDEN,
        );
    }
}
