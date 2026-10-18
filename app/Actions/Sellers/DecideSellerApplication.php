<?php

namespace App\Actions\Sellers;

use App\Models\BlockedEmail;
use App\Models\Seller;
use App\Models\SellerApplication;
use App\Notifications\SellerApplicationDecided;
use Illuminate\Support\Facades\DB;

/**
 * Every admin decision on a seller application, in one place.
 *
 * Each decision is a single transaction and sends exactly one email, so the
 * applicant's view and their inbox can never disagree with the database.
 */
class DecideSellerApplication
{
    public function __construct(
        private readonly ApproveSellerApplication $approver,
    ) {}

    /** Unlocks the account and publishes the storefront. */
    public function approve(SellerApplication $application, string $by): Seller
    {
        $storefront = $this->approver->handle($application, $by);

        // Approving clears any earlier block on that address.
        BlockedEmail::unblock($this->emailFor($application));

        $this->notify($application->refresh(), SellerApplication::STATUS_APPROVED);

        return $storefront;
    }

    /** Declines the application and bars the address from applying again. */
    public function reject(SellerApplication $application, string $reason, string $by): void
    {
        DB::transaction(function () use ($application, $reason, $by): void {
            $this->approver->reject($application, $reason, $by);

            BlockedEmail::block(
                $this->emailFor($application),
                BlockedEmail::SCOPE_SELLER,
                $reason,
                $by,
            );
        });

        $this->notify($application->refresh(), SellerApplication::STATUS_REJECTED);
    }

    /** Still undecided; tells the applicant what is missing. */
    public function hold(SellerApplication $application, string $note, string $by): void
    {
        $application->update([
            'status' => SellerApplication::STATUS_ON_HOLD,
            'reviewed_at' => now(),
            'reviewed_by' => $by,
            'decision_note' => $note,
        ]);

        $this->notify($application->refresh(), SellerApplication::STATUS_ON_HOLD);
    }

    /** Hides it from the queue. Changes nothing for the applicant, so no email. */
    public function archive(SellerApplication $application): void
    {
        $application->update(['archived_at' => now()]);
    }

    public function unarchive(SellerApplication $application): void
    {
        $application->update(['archived_at' => null]);
    }

    /** Removes the record. The account and any block are left untouched. */
    public function delete(SellerApplication $application): void
    {
        $application->delete();
    }

    public function unblock(SellerApplication $application): void
    {
        BlockedEmail::unblock($this->emailFor($application));
    }

    public function isBlocked(SellerApplication $application): bool
    {
        return BlockedEmail::isBlocked($this->emailFor($application));
    }

    private function notify(SellerApplication $application, string $decision): void
    {
        $application->sellerAccount?->notify(
            new SellerApplicationDecided($application, $decision),
        );
    }

    private function emailFor(SellerApplication $application): string
    {
        return (string) $application->sellerAccount?->email;
    }
}
