<?php

namespace App\Actions\Premium;

use App\Actions\Notices\IssueNotice;
use App\Models\Admin;
use App\Models\Listing;
use App\Models\Seller;
use App\Support\PremiumStatus;
use App\Support\StaffBoard;
use Illuminate\Support\Facades\DB;

/**
 * Every admin decision about premium standing and certificates.
 *
 * One action, the same shape as DecideSellerApplication and DecideCase,
 * so the panel, an API and a console command cannot drift about what
 * "grant premium" means.
 *
 * Premium and the certificate are granted separately on purpose: a
 * seller may be premium while their paperwork is still being checked,
 * and withdrawing a certificate must not cost them their standing.
 *
 * Every decision tells the seller — the admin's words or the default —
 * and tells open admin pages to re-read.
 */
class DecidePremium
{
    public function __construct(private readonly IssueNotice $notices) {}

    /** @param  int|null  $months  Null runs indefinitely. */
    public function grant(Seller $seller, ?int $months = null, ?Admin $admin = null, ?string $message = null): Seller
    {
        $seller->update([
            'premium_since' => $seller->premium_since ?? now(),
            'premium_until' => $months === null ? null : now()->addMonths($months),
        ]);

        $this->tell($seller, 'premium_granted', $message, $admin, route('seller.listings'));

        return $seller->refresh();
    }

    /**
     * End premium standing.
     *
     * Featured listings go with it — a slot is a premium benefit, and
     * leaving them up would sell something the seller no longer has.
     * The certificate is deliberately left alone: it says an admin
     * checked them, which stays true.
     */
    public function revoke(Seller $seller, ?Admin $admin = null, ?string $message = null): Seller
    {
        $revoked = DB::transaction(function () use ($seller): Seller {
            $seller->update(['premium_since' => null, 'premium_until' => null]);

            Listing::query()
                ->where('seller_id', $seller->id)
                ->where('feature_state', Listing::FEATURE_ACTIVE)
                ->update([
                    'feature_state' => null,
                    'featured_at' => null,
                    'featured_until' => null,
                ]);

            return $seller->refresh();
        });

        $this->tell($revoked, 'premium_revoked', $message, $admin, route('seller.dashboard'));

        return $revoked;
    }

    /**
     * Issue the certificate that appears on every listing they publish.
     *
     * The reference is kept once issued: re-certifying a seller must not
     * invalidate a reference a buyer has already seen or scanned.
     */
    public function certify(Seller $seller, Admin $admin, ?string $note = null, ?string $message = null): Seller
    {
        $seller->update([
            'certificate_reference' => $seller->certificate_reference
                ?? PremiumStatus::newCertificateReference(),
            'certified_at' => now(),
            'certified_by' => $admin->id,
            'certificate_note' => $note,
        ]);

        $seller->refresh();

        $this->tell($seller, 'certified', $message, $admin, route('seller.storefront'), [
            'reference' => (string) $seller->certificate_reference,
        ]);

        return $seller;
    }

    /**
     * Withdraw it.
     *
     * The reference is cleared as well, so nothing can be presented as
     * a live certificate afterwards.
     */
    public function decertify(Seller $seller, ?Admin $admin = null, ?string $message = null): Seller
    {
        $seller->update([
            'certificate_reference' => null,
            'certified_at' => null,
            'certified_by' => null,
            'certificate_note' => null,
        ]);

        $this->tell($seller, 'decertified', $message, $admin, route('seller.dashboard'));

        return $seller->refresh();
    }

    /** @param  array<string, string>  $replace */
    private function tell(Seller $seller, string $kind, ?string $message, ?Admin $admin, string $link, array $replace = []): void
    {
        $this->notices->send($seller->account, $kind, $message, $admin, $seller, $replace, $link);

        StaffBoard::changed(StaffBoard::PREMIUM);
    }
}
