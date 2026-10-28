<?php

namespace App\Actions\Premium;

use App\Models\Admin;
use App\Models\Listing;
use App\Support\PlatformSettings;
use App\Support\PremiumStatus;
use RuntimeException;

/**
 * Admin decisions on a single listing: approval, and featuring.
 *
 * Both are things a seller asks for and an admin grants, so they live
 * together — and both stamp who decided and when, because "why is my
 * listing still not up" is a question with an answer.
 */
class DecideListingReview
{
    /** A seller asks for approval; the listing waits rather than going live. */
    public function requestApproval(Listing $listing): Listing
    {
        $listing->update([
            'approval_state' => Listing::APPROVAL_PENDING,
            'approval_requested_at' => now(),
            'approval_note' => null,
        ]);

        return $listing->refresh();
    }

    public function approve(Listing $listing, Admin $admin): Listing
    {
        $listing->update([
            'approval_state' => Listing::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'approval_note' => null,
        ]);

        return $listing->refresh();
    }

    /**
     * Reject, with a reason.
     *
     * The reason is required by the caller rather than optional: a
     * rejection a seller cannot act on is worse than none, because they
     * resubmit the same listing.
     */
    public function reject(Listing $listing, Admin $admin, string $reason): Listing
    {
        $listing->update([
            'approval_state' => Listing::APPROVAL_REJECTED,
            'approved_at' => null,
            'approved_by' => $admin->id,
            'approval_note' => $reason,
        ]);

        return $listing->refresh();
    }

    /**
     * A premium seller asks for a feature slot.
     *
     * Checked here, not only in the view: a hidden button is not a
     * control, and this is the only path that sets the state.
     */
    public function requestFeature(Listing $listing): Listing
    {
        if (! PremiumStatus::canRequestFeature($listing->seller)) {
            throw new RuntimeException(__('Featuring is for premium sellers.'));
        }

        if ($listing->status !== Listing::STATUS_ACTIVE) {
            throw new RuntimeException(__('Publish the listing first.'));
        }

        $listing->update([
            'feature_state' => Listing::FEATURE_REQUESTED,
            'feature_requested_at' => now(),
        ]);

        return $listing->refresh();
    }

    /**
     * Grant the slot.
     *
     * Premium is re-checked at the moment of granting: a request may
     * have sat in the queue while the seller's standing lapsed.
     *
     * @param  int|null  $days  Null runs until an admin ends it.
     */
    public function feature(Listing $listing, ?int $days = null): Listing
    {
        if (! PremiumStatus::isPremium($listing->seller)) {
            throw new RuntimeException(__('That seller is no longer premium.'));
        }

        $listing->update([
            'feature_state' => Listing::FEATURE_ACTIVE,
            'featured_at' => now(),
            'featured_until' => $days === null ? null : now()->addDays($days),
        ]);

        return $listing->refresh();
    }

    public function declineFeature(Listing $listing): Listing
    {
        $listing->update([
            'feature_state' => Listing::FEATURE_DECLINED,
            'featured_at' => null,
            'featured_until' => null,
        ]);

        return $listing->refresh();
    }

    public function unfeature(Listing $listing): Listing
    {
        $listing->update([
            'feature_state' => null,
            'featured_at' => null,
            'featured_until' => null,
        ]);

        return $listing->refresh();
    }

    /** Is the feature queue worth showing an admin at all? */
    public function queueOpen(): bool
    {
        return PlatformSettings::featuringEnabled();
    }
}
