<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\SellerAccount;
use App\Notifications\PasswordChanged;
use App\Support\AuthEmailThrottle;
use App\Support\RouteSegments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * Password change for a signed-in buyer or seller.
 *
 * The change itself is never throttled; the confirmation email is, so repeated
 * changes cannot be used to flood an inbox.
 */
class PasswordChangeController extends Controller
{
    public function update(Request $request, string $area): RedirectResponse
    {
        $guard = RouteSegments::guardFor($area);
        abort_if($guard === null, 404);

        $account = $request->user($guard);
        abort_unless($account instanceof Buyer || $account instanceof SellerAccount, 403);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::default()],
        ]);

        if (! Hash::check($validated['current_password'], $account->passwordHash())) {
            throw ValidationException::withMessages([
                'current_password' => __('That is not your current password.'),
            ]);
        }

        if (Hash::check($validated['password'], $account->passwordHash())) {
            throw ValidationException::withMessages([
                'password' => __('Choose a password you have not used here before.'),
            ]);
        }

        $account->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        // Keep this session signed in after the credential change.
        $request->session()->regenerate();

        $message = $this->notify($account, $guard);

        return back()->with('status', $message);
    }

    /**
     * Sends the "your password changed" notice, unless the inbox has had
     * enough of them for now.
     */
    private function notify(Buyer|SellerAccount $account, string $area): string
    {
        $throttle = AuthEmailThrottle::change($area.'|'.$account->emailAddress());

        if ($throttle->tooManyAttempts()) {
            return __('Password updated. We did not email a confirmation: too many sent, try again in :seconds seconds.', [
                'seconds' => $throttle->availableIn(),
            ]);
        }

        $throttle->hit();
        $account->notify(new PasswordChanged($area));

        return __('Password updated. We have emailed you a confirmation.');
    }
}
