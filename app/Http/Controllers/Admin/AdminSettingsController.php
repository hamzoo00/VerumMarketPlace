<?php

namespace App\Http\Controllers\Admin;

use App\Events\PlatformSettingsChanged;
use App\Http\Controllers\Concerns\RespondsToActions;
use App\Http\Controllers\Controller;
use App\Support\ClosureWindow;
use App\Support\NoticeCatalogue;
use App\Support\PlatformSettings;
use App\Support\StaffBoard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The switches that change how the marketplace runs, and the words it
 * uses when an admin does not write their own.
 *
 * Divided into sections (config/verum_platform.php). Each shows its own
 * settings and the default message for every action taken from its part
 * of the admin; the messages are edited by AdminNoticeTemplateController.
 *
 * A save takes effect at once and is announced: open admin pages that
 * show the section re-read themselves, and so do sellers' pages — a
 * seller with the listing form open sees "Submit for approval" turn into
 * "Publish" the moment approval is switched off.
 */
class AdminSettingsController extends Controller
{
    use RespondsToActions;

    /** Validation for each setting a section may save. */
    private const RULES = [
        'commission_enabled' => ['nullable', 'boolean'],
        // Bounds mirror the schema; PlatformSettings clamps as well, so
        // a crafted request cannot slip a 900% rate past this.
        'commission_percent' => ['required', 'numeric', 'min:0', 'max:50'],
        'listing_approval_required' => ['nullable', 'boolean'],
        'featuring_enabled' => ['nullable', 'boolean'],
        'featured_slots' => ['required', 'integer', 'min:1', 'max:24'],
    ];

    public function edit(Request $request): View
    {
        $sections = config('verum_platform.sections');
        $current = $request->query('section');
        $current = is_string($current) && isset($sections[$current]) ? $current : array_key_first($sections);
        $category = $sections[$current]['notices'];

        return view('pages.admin.settings', [
            'sections' => $sections,
            'current' => $current,
            'settings' => PlatformSettings::all(),
            'notices' => $category === null ? [] : NoticeCatalogue::kinds($category),
            'disputeDays' => ClosureWindow::disputeDays(),
        ]);
    }

    public function update(Request $request, string $section): JsonResponse|RedirectResponse
    {
        $keys = config("verum_platform.sections.{$section}.settings");

        abort_unless(is_array($keys) && $keys !== [], 404);

        $validated = $request->validate(array_intersect_key(self::RULES, array_flip($keys)));
        $schema = PlatformSettings::schema();

        $values = collect($keys)->mapWithKeys(fn (string $key): array => [
            // An unchecked box posts nothing at all, so each toggle is read
            // as present-or-absent rather than trusted to arrive.
            $key => ($schema[$key]['type'] ?? null) === 'bool'
                ? $request->boolean($key)
                : $validated[$key],
        ])->all();

        PlatformSettings::put($values, $request->user('admin')?->id);

        /** @var list<string> $areas */
        $areas = config("verum_platform.sections.{$section}.staff_areas", []);

        StaffBoard::changed(StaffBoard::SETTINGS, ...$areas);
        PlatformSettingsChanged::dispatch($section);

        return $this->done($request, __(':section settings saved.', [
            'section' => __(config("verum_platform.sections.{$section}.label")),
        ]));
    }
}
