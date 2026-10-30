import { delegate, onceGlobal } from './dom.js';
import { openNoticeDialog } from './notice-dialog.js';
import { toast } from './toast.js';

/**
 * Forms that act without leaving the page.
 *
 * A form marked data-live-form is sent with fetch and asks for JSON,
 * which RespondsToActions answers with the same sentence a redirect
 * would have flashed. Success raises that as a toast and tells every
 * live region to re-read itself; a refusal or a validation error is
 * shown the same way. Without JavaScript the very same form posts
 * normally — nothing here is needed for the action to work.
 *
 * A form that also carries data-notice writes to a seller or buyer, so
 * it goes through the message dialog first.
 */

const FAILED = 'That did not go through. Please try again.';
const EXPIRED = 'Your session has expired. Reload the page and try again.';

function firstError(data) {
    const errors = data?.errors ? Object.values(data.errors).flat() : [];

    return errors[0] ?? data?.message ?? null;
}

async function send(form, submitter) {
    // The clicked button's name and value travel too, as a real submit's would.
    const body = new FormData(form, submitter ?? undefined);
    const buttons = [...form.querySelectorAll('button')];

    buttons.forEach((button) => { button.disabled = true; });
    form.setAttribute('aria-busy', 'true');

    try {
        // Never form.action: a field named "action" (approve/reject, warn/
        // suspend) shadows it, and the request went to "[object
        // HTMLInputElement]". The attribute cannot be shadowed.
        const response = await fetch(form.getAttribute('action') || window.location.href, {
            method: 'POST',
            body,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });

        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            // Sent, so no longer unsaved work: let its region re-read. Safari
            // leaves focus in the field after a click, which would read as
            // still typing.
            delete form.dataset.liveDirty;
            if (form.contains(document.activeElement)) document.activeElement.blur();

            toast(data.message ?? 'Done.', data.status === 'error' ? 'error' : 'success');
            document.dispatchEvent(new CustomEvent('verum:live-refresh', { detail: { source: 'form' } }));
            return;
        }

        toast(response.status === 419 ? EXPIRED : (firstError(data) ?? FAILED), 'error');
    } catch {
        toast(FAILED, 'error');
    } finally {
        buttons.forEach((button) => { button.disabled = false; });
        form.removeAttribute('aria-busy');
    }
}

export function initLiveForms() {
    // A form with unsent changes holds its live region still (live-regions.js).
    onceGlobal('live-forms-dirty', () => delegate(
        document,
        'input',
        'form[data-live-form] input, form[data-live-form] textarea, form[data-live-form] select',
        (event, field) => {
            if (field.form) field.form.dataset.liveDirty = 'true';
        },
    ));

    onceGlobal('live-forms', () => {
        delegate(document, 'submit', 'form[data-live-form]', (event, form) => {
            // validate.js listens on the form itself, so it has already
            // had its say; a form it stopped stays stopped.
            if (event.defaultPrevented) return;

            event.preventDefault();

            const submitter = event.submitter;

            if (!form.hasAttribute('data-notice')) {
                send(form, submitter);
                return;
            }

            openNoticeDialog(form, (message) => {
                const field = form.querySelector('input[name="message"]');
                if (field) field.value = message;

                send(form, submitter).finally(() => {
                    if (field) field.value = '';
                });
            });
        });
    });
}
