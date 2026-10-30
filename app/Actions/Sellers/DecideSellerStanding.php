<?php

namespace App\Actions\Sellers;

use App\Actions\Notices\IssueNotice;
use App\Actions\Orders\AdvanceOrder;
use App\Events\ListingChanged;
use App\Models\AccountNotice;
use App\Models\Admin;
use App\Models\BlockedEmail;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Support\ClosureWindow;
use App\Support\StaffBoard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every admin decision about an approved seller's standing.
 *
 * Warn, suspend, reinstate, close — one action, the same shape as
 * DecideSellerApplication and DecideCase, so the panel, an API and the
 * closure sweep cannot drift about what "suspended" means.
 *
 * Each decision does three things after it commits: tells the seller
 * (email, bell, live), tells open shop pages that the storefront's
 * stones came or went, and tells open admin pages to re-read.
 *
 * Suspending hides the storefront without touching a listing row, so
 * reinstating restores the catalogue exactly. Closing is a wind-down:
 * what was never dispatched is cancelled and the buyer gets an apology;
 * what is on the road keeps the account open until it can arrive and be
 * disputed; only then does the account close for good.
 */
class DecideSellerStanding
{
    public function __construct(
        private readonly IssueNotice $notices,
        private readonly AdvanceOrder $orders,
    ) {}

    public function warn(SellerAccount $account, Admin $admin, ?string $message = null): ?AccountNotice
    {
        $this->requireApproved($account);

        $notice = $this->notices->send($account, 'warning', $message, $admin, link: route('seller.dashboard'));

        StaffBoard::changed(StaffBoard::SELLERS);

        return $notice;
    }

    public function suspend(SellerAccount $account, Admin $admin, ?string $message = null): SellerAccount
    {
        $this->requireApproved($account);

        [$account, $withdrawn] = DB::transaction(function () use ($account): array {
            $locked = SellerAccount::query()->lockForUpdate()->findOrFail($account->id);

            if ($locked->isSuspended() || $locked->closing_started_at !== null) {
                throw ValidationException::withMessages([
                    'standing' => __('This seller is already suspended or closing.'),
                ]);
            }

            $withdrawn = $this->visibleListings($locked->storefront);

            $locked->update(['suspended_at' => now()]);
            $locked->storefront?->update(['hidden_at' => now()]);

            return [$locked, $withdrawn];
        });

        $this->announce($withdrawn, ListingChanged::DROP);
        $this->notices->send($account, 'suspended', $message, $admin, link: route('seller.dashboard'));
        StaffBoard::changed(StaffBoard::SELLERS);

        return $account;
    }

    public function reinstate(SellerAccount $account, Admin $admin, ?string $message = null): SellerAccount
    {
        $account = DB::transaction(function () use ($account): SellerAccount {
            $locked = SellerAccount::query()->lockForUpdate()->findOrFail($account->id);

            if (! $locked->isSuspended() || $locked->closing_started_at !== null) {
                throw ValidationException::withMessages([
                    'standing' => __('Only a suspended seller can be reinstated.'),
                ]);
            }

            $locked->update(['suspended_at' => null]);
            $locked->storefront?->update(['hidden_at' => null]);

            return $locked;
        });

        $this->announce($this->visibleListings($account->storefront()->first()), ListingChanged::PUT);
        $this->notices->send($account, 'reinstated', $message, $admin, link: route('seller.dashboard'));
        StaffBoard::changed(StaffBoard::SELLERS);

        return $account;
    }

    /**
     * Start closing the account.
     *
     * Undispatched orders are cancelled — nothing has left the seller, so
     * nobody is out of pocket once refunded — and each buyer is told why.
     * Every listing comes down. The account then stays open, hidden and
     * unable to sell, until the last parcel on the road could arrive and
     * its dispute window has passed; with nothing on the road it closes now.
     */
    public function close(SellerAccount $account, Admin $admin, ?string $message = null): SellerAccount
    {
        $this->requireApproved($account);

        [$account, $withdrawn, $cancelled] = DB::transaction(function () use ($account, $admin): array {
            $locked = SellerAccount::query()->lockForUpdate()->findOrFail($account->id);

            if ($locked->closing_started_at !== null) {
                throw ValidationException::withMessages([
                    'standing' => __('This account is already closing.'),
                ]);
            }

            $storefront = $locked->storefront;
            $withdrawn = $this->visibleListings($storefront);
            $cancelled = $storefront === null ? collect() : $this->cancelUndispatched($storefront);

            if ($storefront !== null) {
                // After the cancellations, which put their items back on
                // sale: a closing store sells nothing at all.
                Listing::query()
                    ->where('seller_id', $storefront->id)
                    ->where('status', Listing::STATUS_ACTIVE)
                    ->update(['status' => Listing::STATUS_PAUSED, 'published_at' => null]);

                $storefront->update(['hidden_at' => $storefront->hidden_at ?? now()]);
            }

            $endsAt = $storefront === null ? null : ClosureWindow::endsAt($storefront);

            $locked->update([
                'closing_started_at' => now(),
                'closes_at' => $endsAt ?? now(),
                'closed_at' => $endsAt === null ? now() : null,
            ]);

            // A closed seller does not come back through the front door.
            BlockedEmail::block(
                $locked->email,
                BlockedEmail::SCOPE_SELLER,
                __('Seller account closed.'),
                $admin->username,
            );

            return [$locked, $withdrawn, $cancelled];
        });

        $this->announce($withdrawn, ListingChanged::DROP);
        $this->apologise($cancelled, $admin);

        $this->notices->send(
            $account,
            $account->isClosed() ? 'closed' : 'closing',
            $message,
            $admin,
            replace: ['date' => $account->closes_at?->format('j M Y') ?? ''],
            link: $account->isClosed() ? null : route('seller.orders'),
        );

        StaffBoard::changed(StaffBoard::SELLERS);

        return $account;
    }

    /**
     * Finish a closure whose window has run out.
     *
     * Asks the window again rather than trusting the stored date: a
     * parcel delivered late moves it, and an open case holds it.
     */
    public function finishClosure(SellerAccount $account): bool
    {
        if (! $account->isClosing()) {
            return false;
        }

        $storefront = $account->storefront;
        $endsAt = $storefront === null ? null : ClosureWindow::endsAt($storefront);

        if ($endsAt !== null || ($storefront !== null && ClosureWindow::hasOpenCases($storefront))) {
            $account->update(['closes_at' => $endsAt ?? now()->addDay()]);

            return false;
        }

        $account->update(['closed_at' => now()]);

        $this->notices->send($account, 'closed');
        StaffBoard::changed(StaffBoard::SELLERS);

        return true;
    }

    private function requireApproved(SellerAccount $account): void
    {
        if (! $account->isApproved() || $account->isClosed()) {
            throw ValidationException::withMessages([
                'standing' => __('Only an approved, open seller account can be changed here.'),
            ]);
        }
    }

    /** @return Collection<int, Order> */
    private function cancelUndispatched(Seller $storefront): Collection
    {
        return Order::query()
            ->where('seller_id', $storefront->id)
            ->whereIn('status', [Order::STATUS_AWAITING_PAYMENT, Order::STATUS_PAID])
            ->with('buyer')
            ->get()
            ->map(fn (Order $order): Order => $this->orders->cancel($order, __('The seller closed their store.')));
    }

    /** @param  Collection<int, Order>  $cancelled */
    private function apologise(Collection $cancelled, Admin $admin): void
    {
        foreach ($cancelled as $order) {
            $this->notices->send(
                $order->buyer,
                'order_cancelled_closure',
                admin: $admin,
                about: $order,
                replace: ['order' => $order->reference],
                link: route('buyer.orders.show', $order),
            );
        }
    }

    /**
     * What buyers can see right now, read before the storefront changes,
     * so the drop messages name exactly what left.
     *
     * @return Collection<int, Listing>
     */
    private function visibleListings(?Seller $storefront): Collection
    {
        if ($storefront === null) {
            return collect();
        }

        return Listing::query()
            ->where('seller_id', $storefront->id)
            ->published()
            ->with(['category', 'seller'])
            ->get();
    }

    /** @param  Collection<int, Listing>  $listings */
    private function announce(Collection $listings, string $action): void
    {
        foreach ($listings as $listing) {
            event($action === ListingChanged::PUT ? ListingChanged::put($listing) : ListingChanged::drop($listing));
        }
    }
}
