<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Orders\AdvanceOrder;
use App\Actions\Payments\SettleRefund;
use App\Actions\Shipping\TrackShipment;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Carriers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The seller's sales and their fulfilment.
 *
 * Every status change goes through AdvanceOrder, so the panel cannot
 * invent a transition the rest of the app does not know about.
 *
 * There is no "mark as paid": an order is paid when PayPal says the
 * money moved (CompleteCheckout), never on the seller's word — a button
 * would let a sale be settled off the platform and skip the commission.
 */
class SellerOrderController extends Controller
{
    public function __construct(
        private readonly AdvanceOrder $advance,
        private readonly SettleRefund $refunds,
        private readonly TrackShipment $shipments,
    ) {}

    public function index(Request $request): View
    {
        $storefront = $request->user('seller')->storefront;

        $validated = $request->validate([
            'status' => ['nullable', Rule::in([...array_keys(Order::statusLabels()), 'to_ship', 'all'])],
        ]);

        $filter = $validated['status'] ?? 'to_ship';

        $base = fn () => Order::query()->where('seller_id', $storefront?->id);

        return view('pages.seller.orders', [
            'account' => $request->user('seller'),
            'orders' => $base()
                ->with(['items', 'buyer'])
                ->when($filter === 'to_ship', fn ($query) => $query->toShip())
                ->when(
                    ! in_array($filter, ['to_ship', 'all'], true),
                    fn ($query) => $query->where('status', $filter),
                )
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filter' => $filter,
            'counts' => [
                'to_ship' => $base()->toShip()->count(),
                'awaiting_payment' => $base()->where('status', Order::STATUS_AWAITING_PAYMENT)->count(),
                'all' => $base()->count(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authoriseSeller($request, $order);

        return view('pages.seller.order', [
            'account' => $request->user('seller'),
            'order' => $order->load(['items.listing', 'buyer', 'shipmentEvents']),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authoriseSeller($request, $order);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['dispatched', 'delivered', 'cancelled'])],
            'tracking_carrier' => ['nullable', 'required_with:tracking_number', Rule::in(array_keys(Carriers::options()))],
            'tracking_number' => ['nullable', 'string', 'regex:~^'.Carriers::trackingPattern().'$~'],
            'reason' => ['nullable', 'string', 'max:200'],
        ], [
            'tracking_number.regex' => __('A tracking number is letters, digits and dashes, as printed on the receipt.'),
            'tracking_carrier.required_with' => __('Choose the carrier, so the buyer can follow the parcel.'),
        ]);

        try {
            $message = match ($validated['action']) {
                'dispatched' => $this->dispatch($request, $order, $validated['tracking_carrier'] ?? null, $validated['tracking_number'] ?? null),
                'delivered' => tap(__('Marked as delivered. The buyer has been told.'), fn () => $this->shipments->delivered($order, $request->user('seller'))),
                default => $this->cancel($order, $validated['reason'] ?? null),
            };
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $message);
    }

    private function dispatch(Request $request, Order $order, ?string $carrier, ?string $tracking): string
    {
        $this->shipments->dispatch($order, $request->user('seller'), $carrier, $tracking);

        return $tracking === null
            ? __('Marked as dispatched.')
            : __('Marked as dispatched. The buyer has the tracking link.');
    }

    /** A paid order is refunded through PayPal before it is cancelled. */
    private function cancel(Order $order, ?string $reason): string
    {
        if ($order->status === Order::STATUS_PAID) {
            $this->refunds->cancelPaidOrder($order, $reason);

            return __('Order cancelled and the buyer refunded through PayPal.');
        }

        $this->advance->cancel($order, $reason);

        return __('Order cancelled.');
    }

    /** A seller reaches only their own sales; another gets 403. */
    private function authoriseSeller(Request $request, Order $order): void
    {
        abort_unless(
            (int) $order->seller_id === (int) ($request->user('seller')->storefront?->id),
            Response::HTTP_FORBIDDEN,
        );
    }
}
