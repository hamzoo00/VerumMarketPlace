<?php

namespace App\Support;

use App\Models\Seller;
use Illuminate\Support\Str;

/**
 * What a premium seller may do, and whether they still may.
 *
 * Pure decisions, like AuctionRules and ShippingQuote — the admin panel,
 * the seller's own pages and the public storefront all ask this, so
 * none of them can hold a different opinion about who is premium.
 *
 * Premium and the certificate are **separate**: a seller may be premium
 * while their paperwork is still being checked, and revoking a
 * certificate must not revoke their standing.
 */
final class PremiumStatus
{
    /**
     * Is this seller premium right now?
     *
     * An expiry in the past ends it without anyone running a sweep, the
     * same way a lapsed offer stops counting.
     */
    public static function isPremium(?Seller $seller): bool
    {
        if ($seller?->premium_since === null) {
            return false;
        }

        if ($seller->premium_since->isFuture()) {
            return false;
        }

        return $seller->premium_until === null || $seller->premium_until->isFuture();
    }

    /** Has an admin issued this seller a certificate? */
    public static function isCertified(?Seller $seller): bool
    {
        return $seller?->certified_at !== null && $seller->certificate_reference !== null;
    }

    /**
     * May this seller ask for a listing to be featured?
     *
     * Premium only, and only while the platform has the queue open.
     */
    public static function canRequestFeature(?Seller $seller): bool
    {
        return PlatformSettings::featuringEnabled() && self::isPremium($seller);
    }

    /** Lapsed rather than never granted — worth saying differently. */
    public static function hasExpired(?Seller $seller): bool
    {
        return $seller?->premium_since !== null
            && $seller->premium_until !== null
            && $seller->premium_until->isPast();
    }

    /** A new certificate reference: VRC-2026-A1B2C3. */
    public static function newCertificateReference(): string
    {
        return config('verum_platform.certificate_prefix', 'VRC')
            .'-'.now()->year
            .'-'.strtoupper(Str::random(6));
    }
}
