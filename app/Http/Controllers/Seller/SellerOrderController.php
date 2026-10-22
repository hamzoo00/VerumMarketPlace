<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Orders\AdvanceOrder;
use App\Http\Controllers\Controller;
use App\Models\Order;
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
 */
class SellerOrderController extends Controller
{
    public function __construct(private readonly AdvanceOrder $advance) {}

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
            'order' => $order->load(['items.listing', 'buyer']),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authoriseSeller($request, $order);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['paid', 'dispatched', 'delivered', 'cancelled'])],
            'tracking_carrier' => ['nullable', 'string', 'max:64'],
            'tracking_number' => ['nullable', 'string', 'max:128'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $message = match ($validated['action']) {
                'paid' => tap(__('Marked as paid.'), fn () => $this->advance->markPaid($order)),
                'dispatched' => tap(__('Marked as dispatched.'), fn () => $this->advance->markDispatched(
                    $order,
                    $validated['tracking_carrier'] ?? null,
                    $validated['tracking_number'] ?? null,
                )),
                'delivered' => tap(__('Marked as delivered.'), fn () => $this->advance->markDelivered($order)),
                default => tap(__('Order cancelled.'), fn () => $this->advance->cancel(
                    $order,
                    $validated['reason'] ?? null,
                )),
            };
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $message);
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
