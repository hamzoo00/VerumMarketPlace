<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\SellerAccount;
use App\Support\AuthEmailThrottle;
use App\Support\RouteSegments;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * Forgotten-password flow for buyers and sellers.
 *
 * One controller serves both guards; the account type comes from the route, so
 * the two never share a broker or a token table.
 */
class PasswordResetController extends Controller
{
    /** @var array<string, array{broker: string, login: string}> */
    private const AREAS = [
        'buyer' => ['broker' => 'buyers', 'login' => 'buyer.login'],
        'seller' => ['broker' => 'seller_accounts', 'login' => 'seller.login'],
    ];

    /**
     * The {area} parameter carries the configured URL segment, which may be
     * renamed; this turns it back into the guard name.
     */
    private function guard(string $area): string
    {
        $guard = RouteSegments::guardFor($area);

        abort_if($guard === null, 404);

        return $guard;
    }

    public function request(string $area): View
    {
        $guard = $this->guard($area);

        return view('pages.auth.forgot-password', [
            'area' => $area,
            'guard' => $guard,
            'throttle' => $this->throttleState($guard, null),
        ]);
    }

    public function email(Request $request, string $area): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $guard = $this->guard($area);
        $throttle = AuthEmailThrottle::reset($guard.'|'.$validated['email']);

        if ($throttle->tooManyAttempts()) {
            throw ValidationException::withMessages([
                'email' => __('Too many reset emails. Try again in :seconds seconds.', [
                    'seconds' => $throttle->availableIn(),
                ]),
            ]);
        }

        $throttle->hit();

        Password::broker(self::AREAS[$guard]['broker'])->sendResetLink($validated);

        // Always the same answer, so the form cannot be used to discover
        // which addresses have accounts.
        return back()->with('status', __('If that address has an account, a reset link is on its way.'));
    }

    public function reset(Request $request, string $area, string $token): View
    {
        $guard = $this->guard($area);

        return view('pages.auth.reset-password', [
            'area' => $area,
            'guard' => $guard,
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function update(Request $request, string $area): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::default()],
        ]);

        $guard = $this->guard($area);

        $status = Password::broker(self::AREAS[$guard]['broker'])->reset(
            $validated,
            function (Buyer|SellerAccount $account, string $password): void {
                $account->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($account));
            },
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        AuthEmailThrottle::reset($guard.'|'.$validated['email'])->clear();

        return redirect()
            ->route(self::AREAS[$guard]['login'])
            ->with('status', __('Your password has been reset. Sign in with it now.'));
    }

    /** @return array{remaining: int, max: int, available_in: int} */
    private function throttleState(string $guard, ?string $email): array
    {
        $throttle = AuthEmailThrottle::reset($guard.'|'.($email ?? ''));

        return [
            'remaining' => $throttle->remaining(),
            'max' => $throttle->maxAttempts(),
            'available_in' => $throttle->availableIn(),
        ];
    }
}
