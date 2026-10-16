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
    ];

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

    /** Sellers only reach their dashboard once an admin has approved them. */
    public function canAccessDashboard(): bool
    {
        return $this->isApproved();
    }
}
