<?php

namespace App\Actions\Sellers;

use App\Models\Seller;
use App\Models\SellerApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Approves a seller application.
 *
 * Approval does three things together: stamps the application, unlocks the
 * account, and creates the public storefront. They must not drift apart, so
 * they happen in one transaction.
 */
class ApproveSellerApplication
{
    public function handle(SellerApplication $application, ?string $reviewer = null): Seller
    {
        return DB::transaction(function () use ($application, $reviewer): Seller {
            $account = $application->sellerAccount;

            $application->update([
                'status' => SellerApplication::STATUS_APPROVED,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer,
            ]);

            $account->forceFill(['approved_at' => now()])->save();

            return Seller::updateOrCreate(
                ['seller_account_id' => $account->id],
                [
                    'slug' => $this->uniqueSlug($application->business_name),
                    'name' => $application->business_name,
                    'initials' => Str::upper(Str::substr($application->business_name, 0, 2)),
                    'location' => $application->country,
                    'about' => $application->about ?? '',
                    'ships' => __('Ships from :country', ['country' => $application->country]),
                    'since' => (string) now()->year,
                    'verified_on' => now()->toDateString(),
                    'specialties' => $this->specialties($application),
                ],
            );
        });
    }

    public function reject(SellerApplication $application, string $note, ?string $reviewer = null): void
    {
        $application->update([
            'status' => SellerApplication::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer,
            'decision_note' => $note,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Seller::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /** @return list<string> */
    private function specialties(SellerApplication $application): array
    {
        if (blank($application->specialties)) {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), explode(',', $application->specialties)),
            fn (string $value): bool => $value !== '',
        ));
    }
}
