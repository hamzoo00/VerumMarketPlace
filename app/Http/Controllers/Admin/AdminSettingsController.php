<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PlatformSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The switches that change how the marketplace runs.
 *
 * Commission and listing approval are independent on purpose: a platform
 * that takes no cut may still want to see what goes up, and one that
 * does may trust its sellers.
 */
class AdminSettingsController extends Controller
{
    public function edit(): View
    {
        return view('pages.admin.settings', [
            'settings' => PlatformSettings::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'commission_enabled' => ['nullable', 'boolean'],
            // Bounds mirror the schema; PlatformSettings clamps as well,
            // so a crafted request cannot slip a 900% rate past this.
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'listing_approval_required' => ['nullable', 'boolean'],
            'featuring_enabled' => ['nullable', 'boolean'],
            'featured_slots' => ['required', 'integer', 'min:1', 'max:24'],
        ]);

        PlatformSettings::put([
            // An unchecked box posts nothing at all, so each toggle is
            // read as present-or-absent rather than trusted to arrive.
            'commission_enabled' => $request->boolean('commission_enabled'),
            'commission_percent' => $validated['commission_percent'],
            'listing_approval_required' => $request->boolean('listing_approval_required'),
            'featuring_enabled' => $request->boolean('featuring_enabled'),
            'featured_slots' => $validated['featured_slots'],
        ], $request->user('admin')?->id);

        return back()->with('status', __('Platform settings saved.'));
    }
}
