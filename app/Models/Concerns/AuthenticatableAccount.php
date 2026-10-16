<?php

namespace App\Models\Concerns;

/**
 * Behaviour shared by both account types.
 *
 * Buyers and sellers live in separate tables behind separate guards, so this
 * keeps the unavoidable duplication in one place rather than in both models.
 */
trait AuthenticatableAccount
{
    /**
     * Casts common to every account type.
     *
     * Models merge these into their own casts() rather than overriding them.
     *
     * @return array<string, string>
     */
    protected function accountCasts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
        ];
    }

    public function displayName(): string
    {
        return (string) $this->name;
    }

    public function emailAddress(): string
    {
        return (string) $this->email;
    }

    public function passwordHash(): string
    {
        return (string) $this->password;
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->map(fn (string $word): string => mb_substr($word, 0, 1))
            ->take(2)
            ->implode('');
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }
}
