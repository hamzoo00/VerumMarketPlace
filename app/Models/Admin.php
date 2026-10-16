<?php

namespace App\Models;

use App\Contracts\AuthenticatableAccount as AccountContract;
use App\Models\Concerns\AuthenticatableAccount;
use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * An administrator.
 *
 * Signs in with a username, not an email. There is no public registration and
 * no self-service route to this table: accounts come from `admin:create`.
 *
 * @property Carbon|null $last_login_at
 * @property Carbon|null $two_factor_confirmed_at
 */
class Admin extends Authenticatable implements AccountContract
{
    use AuthenticatableAccount;

    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    use Notifiable;

    protected $table = 'admins';

    /** @var list<string> */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'is_active',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...$this->accountCasts(),
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /** A deactivated admin keeps its row but can no longer authenticate. */
    public function canAccessDashboard(): bool
    {
        return $this->is_active;
    }

    public function recordLogin(): void
    {
        $this->forceFill(['last_login_at' => now()])->saveQuietly();
    }
}
