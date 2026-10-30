<?php

namespace App\Observers;

use App\Events\ListingChanged;
use App\Models\Listing;
use Illuminate\Support\Facades\DB;

/**
 * Announces catalogue changes over WebSockets.
 *
 * This lives on the model rather than in controllers so there is one place
 * that decides what the public sees. Any write path — the seller form, the
 * publish toggle, a media upload, an artisan command — is covered.
 */
class ListingObserver
{
    public function created(Listing $listing): void
    {
        // A listing is born a draft, so this only matters if something
        // created one already live.
        if ($listing->isPubliclyVisible()) {
            $this->announce(ListingChanged::put($listing));
        }
    }

    /**
     * Visibility is a transition, not a state: the same save is an arrival
     * or a departure depending on where the listing came from.
     *
     * "Visible" means what a buyer can see — published, approved if
     * approval applied, and on a storefront that is up — so a listing
     * waiting in the approval queue never reaches an open shop page.
     */
    public function updated(Listing $listing): void
    {
        $wasVisible = $this->wasVisible($listing);
        $isVisible = $listing->isPubliclyVisible();

        if ($isVisible) {
            // Covers both a fresh publish and an edit to something live.
            $this->announce(ListingChanged::put($listing));
        } elseif ($wasVisible) {
            $this->announce(ListingChanged::drop($listing));
        } elseif ($listing->wasChanged(['status', 'published_at', 'approval_state', 'feature_state'])) {
            // Queued, rejected, unpublished-while-hidden: nothing a buyer
            // sees moved, but the seller's inventory chips did.
            $this->announce(ListingChanged::touch($listing));
        }
    }

    public function deleted(Listing $listing): void
    {
        if ($this->wasVisible($listing) || $listing->isPubliclyVisible()) {
            $this->announce(ListingChanged::drop($listing));
        }
    }

    public function restored(Listing $listing): void
    {
        if ($listing->isPubliclyVisible()) {
            $this->announce(ListingChanged::put($listing));
        }
    }

    /**
     * Was it publicly visible *before* this save?
     *
     * The storefront's state is read as it is now: hiding one is not a
     * listing save, and DecideSellerStanding announces those itself.
     */
    private function wasVisible(Listing $listing): bool
    {
        $approval = $listing->getOriginal('approval_state');

        return $listing->getOriginal('status') === Listing::STATUS_ACTIVE
            && $listing->getOriginal('published_at') !== null
            && in_array($approval, [null, Listing::APPROVAL_APPROVED], true)
            && ! ($listing->seller?->isHidden() ?? false);
    }

    /**
     * The event is built now, while the row is still readable, and only its
     * delivery is deferred: a broadcast sent from inside a transaction can
     * otherwise reach a browser that then reads a row not yet committed.
     */
    private function announce(ListingChanged $event): void
    {
        DB::connection()->afterCommit(fn () => event($event));
    }
}
