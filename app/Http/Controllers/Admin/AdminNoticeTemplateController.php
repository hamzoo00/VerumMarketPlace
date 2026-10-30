<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Notices\IssueNotice;
use App\Http\Controllers\Concerns\RespondsToActions;
use App\Http\Controllers\Controller;
use App\Support\NoticeCatalogue;
use App\Support\NoticeTemplates;
use App\Support\StaffBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Editing the standard messages from Settings.
 *
 * An edit replaces the wording used from then on; it never touches what
 * anyone was already sent, which `account_notices` keeps word for word.
 * Resetting returns the kind to the default in config/verum_notices.php.
 */
class AdminNoticeTemplateController extends Controller
{
    use RespondsToActions;

    public function update(Request $request, string $kind): JsonResponse|RedirectResponse
    {
        abort_unless(NoticeCatalogue::has($kind), 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:'.IssueNotice::MAX_LENGTH],
        ]);

        // One line for the subject; plain text for both — this goes into
        // an email and the bell, neither a place for markup.
        $subject = Str::squish(strip_tags($validated['subject']));
        $body = IssueNotice::clean($validated['body']);

        if ($subject === '' || $body === null) {
            throw ValidationException::withMessages([
                $subject === '' ? 'subject' : 'body' => __('This cannot be left empty.'),
            ]);
        }

        $this->requireKnownPlaceholders($kind, $subject.' '.$body);

        NoticeTemplates::save($kind, $subject, $body, $request->user('admin')?->id);
        StaffBoard::changed(StaffBoard::SETTINGS);

        return $this->done($request, __('“:label” saved. It is used from the next message on.', [
            'label' => NoticeCatalogue::label($kind),
        ]));
    }

    /** Back to the standard wording in code. */
    public function destroy(Request $request, string $kind): JsonResponse|RedirectResponse
    {
        abort_unless(NoticeCatalogue::has($kind), 404);

        NoticeTemplates::reset($kind);
        StaffBoard::changed(StaffBoard::SETTINGS);

        return $this->done($request, __('“:label” is back to the standard wording.', [
            'label' => NoticeCatalogue::label($kind),
        ]));
    }

    /**
     * A placeholder this kind cannot fill would reach the seller as
     * written — ":lisitng" in the middle of a sentence. Refused, with the
     * ones that do work.
     */
    private function requireKnownPlaceholders(string $kind, string $text): void
    {
        $unknown = NoticeTemplates::unknownPlaceholders($kind, $text);

        if ($unknown === []) {
            return;
        }

        throw ValidationException::withMessages([
            'body' => __(':found cannot be filled in for this message. You can use :allowed.', [
                'found' => implode(', ', array_map(fn (string $name): string => ':'.$name, $unknown)),
                'allowed' => implode(', ', array_map(
                    fn (string $name): string => ':'.$name,
                    NoticeTemplates::placeholders($kind),
                )),
            ]),
        ]);
    }
}
