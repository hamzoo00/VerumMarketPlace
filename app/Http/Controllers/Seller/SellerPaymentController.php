<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Payments\ConnectPayPal;
use App\Actions\Payments\ManagePremium;
use App\Http\Controllers\Controller;
use App\Models\Money;
use App\Models\Payment;
use App\Models\Seller;
use App\Support\Payments\PaymentFailed;
use App\Support\PlatformSettings;
use App\Support\PremiumStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Where a seller is paid, and their premium subscription.
 *
 * Both are trips to PayPal and back. The return pages only finish what
 * this store started — the actions check the token or subscription id
 * against the storefront — so a link from someone else changes nothing.
 */
class SellerPaymentController extends Controller
{
    public function show(Request $request): View
    {
        $storefront = $this->storefront($request);

        return view('pages.seller.payments', [
            'account' => $request->user('seller'),
            'storefront' => $storefront,
            'commission' => PlatformSettings::commissionRate(),
            'premiumPrice' => Money::fromCents(
                (int) config('verum_payments.premium.price_cents'),
                (string) config('verum_payments.currency'),
            ),
            'isPremium' => PremiumStatus::isPremium($storefront),
            'payments' => Payment::query()
                ->where('seller_id', $storefront->id)
                ->whereIn('status', [
                    Payment::STATUS_COMPLETED,
                    Payment::STATUS_PENDING,
                    Payment::STATUS_REFUNDED,
                    Payment::STATUS_PARTIALLY_REFUNDED,
                    Payment::STATUS_REVERSED,
                ])
                ->with('order')
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function connect(Request $request, ConnectPayPal $connect): RedirectResponse
    {
        return $this->toPayPal(fn (): string => $connect->start($this->storefront($request)));
    }

    /** PayPal sends the seller back here, connected or not. */
    public function connected(Request $request, ConnectPayPal $connect): RedirectResponse
    {
        $token = $request->query('merchantId');
        $merchant = $request->query('merchantIdInPayPal');

        if (! is_string($token) || ! is_string($merchant)) {
            return redirect()->route('seller.payments')
                ->with('info', __('PayPal was not connected. You can try again whenever you are ready.'));
        }

        try {
            $connect->finish($this->storefront($request), $token, $merchant);
        } catch (PaymentFailed $e) {
            return redirect()->route('seller.payments')->with('error', $e->getMessage());
        }

        return redirect()->route('seller.payments')
            ->with('status', __('PayPal connected. Buyers can now pay you directly.'));
    }

    public function disconnect(Request $request, ConnectPayPal $connect): RedirectResponse
    {
        $connect->disconnect($this->storefront($request));

        return redirect()->route('seller.payments')
            ->with('warning', __('PayPal disconnected. Buyers cannot pay you until you connect an account again.'));
    }

    public function subscribe(Request $request, ManagePremium $premium): RedirectResponse
    {
        return $this->toPayPal(fn (): string => $premium->start($this->storefront($request)));
    }

    public function subscribed(Request $request, ManagePremium $premium): RedirectResponse
    {
        $id = $request->query('subscription_id');

        if (! is_string($id)) {
            return redirect()->route('seller.payments');
        }

        try {
            $seller = $premium->confirm($this->storefront($request), $id);
        } catch (PaymentFailed $e) {
            return redirect()->route('seller.payments')->with('error', $e->getMessage());
        }

        return redirect()->route('seller.payments')->with('status', PremiumStatus::isPremium($seller)
            ? __('Welcome to premium. You can now ask for listings to be featured.')
            : __('PayPal is still setting up your subscription. Premium switches on the moment it is active.'));
    }

    public function unsubscribe(Request $request, ManagePremium $premium): RedirectResponse
    {
        try {
            $seller = $premium->cancel($this->storefront($request));
        } catch (PaymentFailed $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('seller.payments')->with('status', $seller->premium_until?->isFuture()
            ? __('Subscription cancelled. Premium stays on until :date.', ['date' => $seller->premium_until->format('j M Y')])
            : __('Subscription cancelled.'));
    }

    /** @param  callable(): string  $start */
    private function toPayPal(callable $start): RedirectResponse
    {
        try {
            return redirect()->away($start());
        } catch (PaymentFailed $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function storefront(Request $request): Seller
    {
        $storefront = $request->user('seller')->storefront;

        abort_if($storefront === null, Response::HTTP_FORBIDDEN);

        return $storefront;
    }
}
