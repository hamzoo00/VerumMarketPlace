<?php

namespace App\Http\Controllers;

use App\Support\DisplayCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

/**
 * Switches the currency a visitor sees prices quoted in.
 *
 * A display preference, so it is a cookie rather than an account setting:
 * a signed-out visitor must be able to set it, and it has to outlive the
 * tab. Nothing here changes what anyone is charged — the site transacts
 * in USD and this only decides the bracketed figure.
 */
class CurrencyController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Only a country the rest of the site knows, so a crafted
            // value cannot put an arbitrary string in the cookie.
            'country' => ['required', 'string', 'size:2', Rule::in(array_keys(DisplayCurrency::options()))],
        ]);

        $iso = strtoupper($validated['country']);

        // Back to where they were: the price they were looking at is the
        // reason they switched.
        return back()->withCookie(Cookie::make(
            DisplayCurrency::cookieName(),
            $iso,
            DisplayCurrency::cookieDays() * 24 * 60,
        ));
    }
}
