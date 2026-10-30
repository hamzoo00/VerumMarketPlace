<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * One answer for an action, whoever asked.
 *
 * A form posted the ordinary way gets a redirect and a flashed toast;
 * one sent by live-forms.js asks for JSON and gets the same sentence,
 * then the page re-reads its own regions. Validation errors already
 * answer both ways on their own, so only success and refusal live here.
 */
trait RespondsToActions
{
    protected function done(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'status' => 'success'])
            : back()->with('status', $message);
    }

    protected function refused(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'status' => 'error'], 422)
            : back()->with('error', $message);
    }
}
