import { $ } from './dom.js';

/**
 * The admin's message step.
 *
 * A form marked data-notice opens the shared <dialog> instead of sending:
 * it says what is about to happen, shows the standard message the seller
 * or buyer will get, and lets the admin write their own. Confirming hands
 * the text back to live-forms.js, which puts it in the form and sends it.
 *
 * Everything shown is set with textContent, never as markup — the
 * standard message is server text, but the habit is what keeps it safe.
 */

let pending = null;

const slot = (dialog, name) => $(`[data-notice-slot="${name}"]`, dialog);

function updateCount(dialog) {
    const field = $('[data-notice-message]', dialog);
    const count = $('[data-notice-count]', dialog);

    if (field && count) count.textContent = String(field.value.length);
}

/**
 * Ask, then call onConfirm(message). An empty message means "send the
 * standard one". Without dialog support the action goes ahead with it.
 */
export function openNoticeDialog(form, onConfirm) {
    const dialog = $('[data-notice-dialog]');

    if (!dialog || typeof dialog.showModal !== 'function') {
        onConfirm('');
        return;
    }

    const { noticeTitle, noticeDefault, noticeLede, noticeConfirm } = form.dataset;
    const danger = form.hasAttribute('data-notice-danger');

    slot(dialog, 'title').textContent = noticeTitle ?? '';
    slot(dialog, 'default').textContent = noticeDefault ?? '';
    slot(dialog, 'confirm').textContent = noticeConfirm ?? 'Send';

    const lede = slot(dialog, 'lede');
    lede.textContent = noticeLede ?? '';
    lede.hidden = !noticeLede;

    dialog.classList.toggle('ndlg--danger', danger);

    const field = $('[data-notice-message]', dialog);
    field.value = '';
    updateCount(dialog);

    pending = onConfirm;
    dialog.returnValue = '';
    dialog.showModal();
    field.focus();
}

export function initNoticeDialog() {
    const dialog = $('[data-notice-dialog]');

    // boot() re-runs on every navigation; bind each dialog element once.
    if (!dialog || dialog.dataset.noticeReady === 'true') return;
    dialog.dataset.noticeReady = 'true';

    dialog.addEventListener('close', () => {
        const callback = pending;
        pending = null;

        if (dialog.returnValue === 'confirm' && callback) {
            callback($('[data-notice-message]', dialog).value.trim());
        }
    });

    $('[data-notice-message]', dialog)?.addEventListener('input', () => updateCount(dialog));

    // A click on the backdrop is a cancel, as Escape already is.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close('cancel');
    });
}
