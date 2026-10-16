<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\SellerAccount;
use App\Models\SellerApplication;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * Seller sign-in and application.
 *
 * Registering creates an account plus a pending application. The account can
 * sign in immediately to watch its status, but the seller area stays locked
 * until an admin approves it.
 */
class SellerAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('pages.auth.seller.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticateUsing('seller');
        $request->session()->regenerate();

        return redirect()->intended(route('seller.dashboard'));
    }

    public function showRegister(): View
    {
        return view('pages.auth.seller.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:seller_accounts,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'business_name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:120'],
            'trade_license_number' => ['nullable', 'string', 'max:120'],
            'specialties' => ['nullable', 'string', 'max:500'],
            'about' => ['nullable', 'string', 'max:2000'],
        ]);

        // The account and its application are created together: an account
        // without an application could never be reviewed.
        $account = DB::transaction(function () use ($validated): SellerAccount {
            $account = SellerAccount::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'country' => $validated['country'],
            ]);

            $account->application()->create([
                'reference' => SellerApplication::generateReference(),
                'status' => SellerApplication::STATUS_SUBMITTED,
                'business_name' => $validated['business_name'],
                'country' => $validated['country'],
                'trade_license_number' => $validated['trade_license_number'] ?? null,
                'specialties' => $validated['specialties'] ?? null,
                'about' => $validated['about'] ?? null,
                'submitted_at' => now(),
            ]);

            return $account;
        });

        event(new Registered($account));

        Auth::guard('seller')->login($account);
        $request->session()->regenerate();

        return redirect()->route('seller.status');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('seller')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
