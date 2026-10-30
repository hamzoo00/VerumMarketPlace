<?php

namespace App\Models;

use App\Contracts\AuthenticatableAccount as AccountContract;
use App\Models\Concerns\AuthenticatableAccount;
use App\Notifications\ResetPassword;
use Database\Factories\SellerAccountFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * A seller's login identity.
 *
 * Separate from `Seller`, which is the public storefront. An account only gains
 * a storefront once an admin approves its application.
 *
 * @property Carbon|null $approved_at
 * @property Carbon|null $suspended_at
 * @property Carbon|null $closing_started_at
 * @property Carbon|null $closes_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $two_factor_confirmed_at
 */
class SellerAccount extends Authenticatable implements AccountContract, MustVerifyEmail
{
    use AuthenticatableAccount;

    /** @use HasFactory<SellerAccountFactory> */
    use HasFactory;

    use Notifiable;

    protected $table = 'seller_accounts';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'country',
        'approved_at',
        'suspended_at',
        'closing_started_at',
        'closes_at',
        'closed_at',
    ];

    /*
     * Where an approved seller stands. Derived from the timestamps rather
     * than stored, so it can never disagree with them.
     */
    public const STANDING_PENDING = 'pending';

    public const STANDING_ACTIVE = 'active';

    public const STANDING_SUSPENDED = 'suspended';

    public const STANDING_CLOSING = 'closing';

    public const STANDING_CLOSED = 'closed';

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /** Points the reset link at this account type's own form. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token, 'seller'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...$this->accountCasts(),
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'closing_started_at' => 'datetime',
            'closes_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return HasOne<SellerApplication, $this> */
    public function application(): HasOne
    {
        return $this->hasOne(SellerApplication::class);
    }

    /**
     * The public storefront, which exists only after approval.
     *
     * @return HasOne<Seller, $this>
     */
    public function storefront(): HasOne
    {
        return $this->hasOne(Seller::class);
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Sellers reach their dashboard once approved, and keep it while
     * suspended or closing — there may be orders to finish and buyers to
     * answer. Only a closed account is shut out.
     */
    public function canAccessDashboard(): bool
    {
        return $this->isApproved() && ! $this->isClosed();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /** Winding down: orders on the road still need finishing. */
    public function isClosing(): bool
    {
        return $this->closing_started_at !== null && $this->closed_at === null;
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * May list, publish, and take orders, bids and offers.
     *
     * The one rule every trading path asks, so a stale page cannot sell
     * on behalf of a seller an admin has stopped.
     */
    public function canTrade(): bool
    {
        return $this->isApproved()
            && ! $this->isSuspended()
            && $this->closing_started_at === null;
    }

    public function standing(): string
    {
        return match (true) {
            $this->isClosed() => self::STANDING_CLOSED,
            $this->isClosing() => self::STANDING_CLOSING,
            $this->isSuspended() => self::STANDING_SUSPENDED,
            $this->isApproved() => self::STANDING_ACTIVE,
            default => self::STANDING_PENDING,
        };
    }

    /** @return array<string, string> */
    public static function standingLabels(): array
    {
        return [
            self::STANDING_PENDING => __('Pending'),
            self::STANDING_ACTIVE => __('Active'),
            self::STANDING_SUSPENDED => __('Suspended'),
            self::STANDING_CLOSING => __('Closing'),
            self::STANDING_CLOSED => __('Closed'),
        ];
    }

    public function accountArea(): string
    {
        return 'seller';
    }
}
