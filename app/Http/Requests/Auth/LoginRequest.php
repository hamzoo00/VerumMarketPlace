<?php

namespace App\Http\Requests\Auth;

use App\Contracts\AuthenticatableAccount;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Credential check shared by both guards.
 *
 * Throttling is keyed per guard so a buyer's failed attempts never lock out a
 * seller using the same email.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /** @throws ValidationException */
    public function authenticateUsing(string $guard): void
    {
        $this->ensureIsNotRateLimited($guard);

        $credentials = $this->only('email', 'password');

        if (! Auth::guard($guard)->attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($guard));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($guard));

        // Told plainly, but only to someone who knew the password — the
        // closure is not something to confirm to anyone who asks.
        $account = Auth::guard($guard)->user();

        if ($account instanceof AuthenticatableAccount && $account->isClosed()) {
            Auth::guard($guard)->logout();

            throw ValidationException::withMessages([
                'email' => __('This account has been closed.'),
            ]);
        }
    }

    /** @throws ValidationException */
    protected function ensureIsNotRateLimited(string $guard): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($guard), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey($guard));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(string $guard): string
    {
        return Str::transliterate(
            $guard.'|'.Str::lower((string) $this->string('email')).'|'.$this->ip(),
        );
    }
}
