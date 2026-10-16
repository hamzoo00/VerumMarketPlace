<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Admin sign-in for the browser.
 *
 * Username and password, no registration, no password reset link: a locked-out
 * admin is restored with `php artisan admin:create <username> --force`.
 */
class AdminSessionController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(): View
    {
        return view('pages.admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = $this->throttleKey($request, $validated['username']);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'username' => __('Too many attempts. Try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($key),
                ]),
            ]);
        }

        if (! Auth::guard('admin')->attempt($validated, $request->boolean('remember'))) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                'username' => __('Those credentials do not match our records.'),
            ]);
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin->is_active) {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages([
                'username' => __('This admin account is not active.'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $admin->recordLogin();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function throttleKey(Request $request, string $username): string
    {
        return Str::transliterate('admin-web|'.Str::lower($username).'|'.$request->ip());
    }
}
