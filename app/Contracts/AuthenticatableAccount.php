<?php

namespace App\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;

/**
 * What the shared auth flows need from an account, whichever table it lives in.
 *
 * Buyers, sellers and admins are separate models behind separate guards; this
 * is the small surface the password flows and notifications rely on.
 */
interface AuthenticatableAccount extends Authenticatable, CanResetPassword
{
    public function canAccessDashboard(): bool;

    /**
     * Closed for good: signing in is refused outright.
     *
     * Distinct from canAccessDashboard(), which a pending seller also
     * fails while still being allowed to sign in and follow their review.
     */
    public function isClosed(): bool;

    /** The account area this identity belongs to: buyer, seller or admin. */
    public function accountArea(): string;

    /** Display name used in emails and greetings. */
    public function displayName(): string;

    /** Address the account's mail is sent to. */
    public function emailAddress(): string;

    /** Hash currently stored for the account. */
    public function passwordHash(): string;
}
