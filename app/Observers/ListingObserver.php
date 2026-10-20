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
        if ($listing->isPublished()) {
            $this->announce(ListingChanged::put($listing));
        }
    }

    /**
     * Visibility is a transition, not a state: the same save is an arrival
     * or a departure depending on where the listing came from.
     */
    public function updated(Listing $listing): void
    {
        $wasVisible = $this->wasPublished($listing);
        $isVisible = $listing->isPublished();

        if ($isVisible) {
            // Covers both a fresh publish and an edit to something live.
            $this->announce(ListingChanged::put($listing));
        } elseif ($wasVisible) {
            $this->announce(ListingChanged::drop($listing));
        }
    }

    public function deleted(Listing $listing): void
    {
        if ($this->wasPublished($listing) || $listing->isPublished()) {
            $this->announce(ListingChanged::drop($listing));
        }
    }

    public function restored(Listing $listing): void
    {
        if ($listing->isPublished()) {
            $this->announce(ListingChanged::put($listing));
        }
    }

    /** Was it publicly visible *before* this save? */
    private function wasPublished(Listing $listing): bool
    {
        $status = $listing->getOriginal('status');
        $publishedAt = $listing->getOriginal('published_at');

        return $status === Listing::STATUS_ACTIVE && $publishedAt !== null;
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
