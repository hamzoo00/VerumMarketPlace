<?php

namespace App\Actions\Cases;

use App\Models\Admin;
use App\Models\Listing;
use App\Models\SupportCase;
use App\Notifications\CaseDecided;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An admin settles a case.
 *
 * Every surface goes through here — the panel, an API, a console
 * command — so the outcome, the audit trail and the two notifications
 * cannot drift apart, the same discipline as DecideSellerApplication.
 *
 * Both parties are always told. Learning the outcome second-hand, or not
 * at all, is how a marketplace loses trust.
 */
class DecideCase
{
    public function review(SupportCase $case): SupportCase
    {
        $case->update(['status' => SupportCase::STATUS_REVIEWING]);

        return $case->refresh();
    }

    public function resolve(SupportCase $case, Admin $admin, string $resolution, ?string $note = null): SupportCase
    {
        if (! array_key_exists($resolution, config('verum_cases.resolutions'))) {
            throw ValidationException::withMessages([
                'resolution' => __('That is not an outcome we recognise.'),
            ]);
        }

        $settled = DB::transaction(function () use ($case, $admin, $resolution, $note): SupportCase {
            $locked = SupportCase::query()->lockForUpdate()->findOrFail($case->id);

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'resolution' => __('That case has already been settled.'),
                ]);
            }

            $locked->update([
                'status' => SupportCase::STATUS_RESOLVED,
                'resolution' => $resolution,
                'resolution_note' => $note,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);

            // Taking the listing down is part of the decision, not a
            // separate step an admin might forget.
            if ($resolution === 'listing_removed') {
                $locked->listing?->update([
                    'status' => Listing::STATUS_PAUSED,
                    'published_at' => null,
                ]);
            }

            return $locked;
        });

        $this->tellBothSides($settled);

        return $settled;
    }

    /**
     * In-app and by email, to the buyer and to the seller.
     *
     * Dispatched after the transaction commits: a notification that
     * arrives before the row is visible would be read as wrong.
     */
    private function tellBothSides(SupportCase $case): void
    {
        $case->loadMissing(['buyer', 'seller', 'listing.seller']);

        $case->buyer?->notify(new CaseDecided($case, 'buyer'));

        // Sellers sign in with an account, not the storefront row.
        $case->subjectSeller()?->account?->notify(new CaseDecided($case, 'seller'));
    }
}
