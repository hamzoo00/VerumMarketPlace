<?php

namespace App\Actions\Premium;

use App\Actions\Notices\IssueNotice;
use App\Models\Admin;
use App\Models\Listing;
use App\Support\NoticeCatalogue;
use App\Support\PlatformSettings;
use App\Support\PremiumStatus;
use App\Support\StaffAlerts;
use App\Support\StaffBoard;
use RuntimeException;

/**
 * Admin decisions on a single listing: approval, and featuring.
 *
 * Both are things a seller asks for and an admin grants, so they live
 * together — and both stamp who decided and when, because "why is my
 * listing still not up" is a question with an answer.
 *
 * Every decision tells the seller, in the admin's words or the default
 * from config/verum_notices.php, and tells open admin queues to re-read.
 */
class DecideListingReview
{
    public function __construct(private readonly IssueNotice $notices) {}

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

    public function approve(Listing $listing, Admin $admin, ?string $message = null): Listing
    {
        $listing->update([
            'approval_state' => Listing::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'approval_note' => null,
        ]);

        $this->tell($listing, 'listing_approved', $message, $admin, route('listings.show', $listing));
        StaffBoard::changed(StaffBoard::APPROVALS);

        return $listing->refresh();
    }

    /**
     * Reject, saying why.
     *
     * The admin may write the reason or leave the default, which asks the
     * seller to check exactly what reviewers look at. Either way the words
     * sent are kept as the listing's note, so the seller's inventory shows
     * the same reason the email gave — a rejection they cannot act on just
     * gets resubmitted unchanged.
     */
    public function reject(Listing $listing, Admin $admin, ?string $message = null): Listing
    {
        $reason = IssueNotice::clean($message)
            ?? NoticeCatalogue::defaultBody('listing_rejected', ['listing' => $listing->title]);

        $listing->update([
            'approval_state' => Listing::APPROVAL_REJECTED,
            'approved_at' => null,
            'approved_by' => $admin->id,
            'approval_note' => $reason,
        ]);

        $this->tell($listing, 'listing_rejected', $message, $admin, route('seller.listings.edit', $listing));
        StaffBoard::changed(StaffBoard::APPROVALS);

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

        StaffAlerts::featureRequested($listing);

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
    public function feature(Listing $listing, ?int $days = null, ?Admin $admin = null, ?string $message = null): Listing
    {
        if (! PremiumStatus::isPremium($listing->seller)) {
            throw new RuntimeException(__('That seller is no longer premium.'));
        }

        $listing->update([
            'feature_state' => Listing::FEATURE_ACTIVE,
            'featured_at' => now(),
            'featured_until' => $days === null ? null : now()->addDays($days),
        ]);

        $this->tell($listing, 'feature_granted', $message, $admin, route('seller.listings'));
        StaffBoard::changed(StaffBoard::FEATURES);

        return $listing->refresh();
    }

    public function declineFeature(Listing $listing, ?Admin $admin = null, ?string $message = null): Listing
    {
        $listing->update([
            'feature_state' => Listing::FEATURE_DECLINED,
            'featured_at' => null,
            'featured_until' => null,
        ]);

        $this->tell($listing, 'feature_declined', $message, $admin, route('seller.listings'));
        StaffBoard::changed(StaffBoard::FEATURES);

        return $listing->refresh();
    }

    public function unfeature(Listing $listing, ?Admin $admin = null, ?string $message = null): Listing
    {
        $listing->update([
            'feature_state' => null,
            'featured_at' => null,
            'featured_until' => null,
        ]);

        $this->tell($listing, 'feature_ended', $message, $admin, route('seller.listings'));
        StaffBoard::changed(StaffBoard::FEATURES);

        return $listing->refresh();
    }

    /** Is the feature queue worth showing an admin at all? */
    public function queueOpen(): bool
    {
        return PlatformSettings::featuringEnabled();
    }

    /** The seller signs in with an account, not the storefront row. */
    private function tell(Listing $listing, string $kind, ?string $message, ?Admin $admin, string $link): void
    {
        $this->notices->send(
            $listing->seller?->account,
            $kind,
            $message,
            $admin,
            $listing,
            ['listing' => $listing->title],
            $link,
        );
    }
}
