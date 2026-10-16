<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Buyer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Buyer sign-in and registration.
 *
 * Buyers need no approval: registering lets them straight into their area.
 */
class BuyerAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('pages.auth.buyer.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticateUsing('buyer');
        $request->session()->regenerate();

        return redirect()->intended(route('buyer.dashboard'));
    }

    public function showRegister(): View
    {
        return view('pages.auth.buyer.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:buyers,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        $buyer = Buyer::create($validated);

        event(new Registered($buyer));

        Auth::guard('buyer')->login($buyer);
        $request->session()->regenerate();

        return redirect()->route('buyer.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('buyer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
