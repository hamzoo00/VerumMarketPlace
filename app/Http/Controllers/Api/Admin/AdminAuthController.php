<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Admin sign-in for the API.
 *
 * Stateful like the rest of the app: a session cookie, not a bearer token.
 */
class AdminAuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = $this->throttleKey($request, $validated['username']);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'username' => __('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($key),
                    'minutes' => ceil(RateLimiter::availableIn($key) / 60),
                ]),
            ]);
        }

        if (! Auth::guard('admin')->attempt($validated, $request->boolean('remember'))) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        $admin = Auth::guard('admin')->user();

        // A deactivated row must not hold a session.
        if (! $admin->is_active) {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages([
                'username' => __('This admin account is not active.'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $admin->recordLogin();

        return response()->json([
            'data' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'name' => $admin->name,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $request->user('admin');

        return response()->json([
            'data' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'name' => $admin->name,
                'email' => $admin->email,
                'last_login_at' => $admin->last_login_at?->toIso8601String(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => __('Signed out.')]);
    }

    private function throttleKey(Request $request, string $username): string
    {
        return Str::transliterate('admin|'.Str::lower($username).'|'.$request->ip());
    }
}
