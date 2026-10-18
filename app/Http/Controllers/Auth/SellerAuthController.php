<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\SellerAccount;
use App\Models\SellerApplication;
use App\Rules\NotBlockedEmail;
use App\Support\Countries;
use App\Support\SellerCategories;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            'email' => [
                'required', 'string', 'email', 'max:255',
                'unique:seller_accounts,email',
                new NotBlockedEmail,
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'business_name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2', Rule::in(array_keys(Countries::all()))],
            'dial_code' => ['required', 'string', 'max:6'],
            'phone' => ['required', 'string', 'min:6', 'max:20'],
            'category' => ['required', Rule::in(array_keys(SellerCategories::options()))],
            'specialties' => ['required', 'array', 'min:1'],
            'specialties.*' => [Rule::in(SellerCategories::allSpecialtyKeys())],
            'trade_license_number' => ['nullable', 'string', 'max:120'],
            'about' => ['nullable', 'string', 'max:2000'],
        ]);

        // The account and its application are created together: an account
        // without an application could never be reviewed.
        $account = DB::transaction(function () use ($validated): SellerAccount {
            // Stored as one E.164 string; the form collects it in two parts.
            $phone = $validated['dial_code'].preg_replace('/\D+/', '', $validated['phone']);
            $countryName = Countries::name($validated['country']) ?? $validated['country'];

            $account = SellerAccount::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'country' => $countryName,
                'phone' => $phone,
            ]);

            $account->application()->create([
                'reference' => SellerApplication::generateReference(),
                'status' => SellerApplication::STATUS_SUBMITTED,
                'business_name' => $validated['business_name'],
                'category' => $validated['category'],
                'specialty_keys' => $validated['specialties'],
                'country' => $countryName,
                'country_code' => $validated['country'],
                'phone' => $phone,
                'trade_license_number' => $validated['trade_license_number'] ?? null,
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
