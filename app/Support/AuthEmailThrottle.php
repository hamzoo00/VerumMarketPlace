<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Throttles password reset and password change emails.
 *
 * Three sends are allowed back to back; the fourth has to wait out the
 * cooldown. Keyed per address and per flow, so a reset never eats into the
 * allowance for a change.
 */
final class AuthEmailThrottle
{
    public function __construct(
        private readonly string $flow,
        private readonly string $identifier,
    ) {}

    public static function reset(string $identifier): self
    {
        return new self('reset', $identifier);
    }

    public static function change(string $identifier): self
    {
        return new self('change', $identifier);
    }

    public function tooManyAttempts(): bool
    {
        return RateLimiter::tooManyAttempts($this->key(), $this->maxAttempts());
    }

    /** Seconds until the next send is allowed; zero when one is available. */
    public function availableIn(): int
    {
        return $this->tooManyAttempts() ? RateLimiter::availableIn($this->key()) : 0;
    }

    public function remaining(): int
    {
        return RateLimiter::remaining($this->key(), $this->maxAttempts());
    }

    public function hit(): void
    {
        RateLimiter::hit($this->key(), $this->decaySeconds());
    }

    public function clear(): void
    {
        RateLimiter::clear($this->key());
    }

    public function maxAttempts(): int
    {
        return (int) config("verum_auth.throttle.{$this->flow}.attempts", 3);
    }

    public function decaySeconds(): int
    {
        return (int) config("verum_auth.throttle.{$this->flow}.decay_seconds", 30);
    }

    private function key(): string
    {
        return Str::transliterate(
            "auth-email:{$this->flow}:".Str::lower($this->identifier),
        );
    }
}
