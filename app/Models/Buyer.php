<?php

namespace App\Models;

use App\Contracts\AuthenticatableAccount as AccountContract;
use App\Models\Concerns\AuthenticatableAccount;
use App\Notifications\ResetPassword;
use Database\Factories\BuyerFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * A buyer account.
 *
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $two_factor_confirmed_at
 */
class Buyer extends Authenticatable implements AccountContract, MustVerifyEmail
{
    use AuthenticatableAccount;

    /** @use HasFactory<BuyerFactory> */
    use HasFactory;

    use Notifiable;

    protected $table = 'buyers';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'country',
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
        $this->notify(new ResetPassword($token, 'buyer'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return $this->accountCasts();
    }

    /** Buyers reach the storefront immediately; there is nothing to approve. */
    public function canAccessDashboard(): bool
    {
        return true;
    }
}
