{{--
    The one dialog every admin action that writes to an account goes
    through. A form marked data-notice (x-verum.admin.notice-form) opens
    it on submit, filled from that form's data-notice-* attributes: what
    is about to happen, who hears about it, and the standard message they
    will get if the admin writes none.

    It is a native <dialog>: focus is trapped and Escape closes it without
    any code of ours. Nothing here is submitted itself — live-forms.js
    copies the message into the original form and sends that.
--}}
<dialog class="ndlg" id="noticeDialog" aria-labelledby="noticeDialogTitle" data-notice-dialog>
    <form method="dialog" class="ndlg__box">
        <header class="ndlg__head">
            <h2 class="ndlg__title" id="noticeDialogTitle" data-notice-slot="title"></h2>
            <button class="ndlg__x" type="submit" value="cancel" aria-label="{{ __('Close') }}">
                <x-verum.icon name="close" />
            </button>
        </header>

        <p class="ndlg__lede" data-notice-slot="lede" hidden></p>

        <div class="ndlg__default">
            <p class="ndlg__label">
                <x-verum.icon name="mail" />{{ __('Standard message') }}
            </p>
            <blockquote data-notice-slot="default"></blockquote>
        </div>

        <div class="ndlg__write">
            <label class="ndlg__label" for="noticeMessage">{{ __('Write your own (optional)') }}</label>
            <textarea
                id="noticeMessage"
                rows="5"
                maxlength="{{ \App\Actions\Notices\IssueNotice::MAX_LENGTH }}"
                placeholder="{{ __('Leave this empty to send the standard message above.') }}"
                data-notice-message
            ></textarea>
            <p class="ndlg__count" aria-live="polite">
                <span data-notice-count>0</span> / {{ \App\Actions\Notices\IssueNotice::MAX_LENGTH }}
            </p>
        </div>

        <p class="ndlg__note">
            <x-verum.icon name="info" />
            {{ __('Sent by email and to their notifications, and kept on record with your name.') }}
        </p>

        <footer class="ndlg__foot">
            <button class="btn btn--ghost btn--sm" type="submit" value="cancel">{{ __('Cancel') }}</button>
            <button class="btn btn--ruby btn--sm" type="submit" value="confirm" data-notice-slot="confirm">{{ __('Send') }}</button>
        </footer>
    </form>
</dialog>
