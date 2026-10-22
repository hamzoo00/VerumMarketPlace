import { $, on, onceGlobal } from './dom.js';

/**
 * The country picker in the header.
 *
 * The list is rendered by Blade and the form posts normally, so this adds
 * only what needs a browser: the popover, and a filter over 100-odd
 * countries that would otherwise be a scroll.
 */

const FADE_MS = 300;

function initFilter(popover) {
    const box = popover.querySelector('[data-cursel-filter]');
    const rows = [...popover.querySelectorAll('[data-cursel-row]')];
    const empty = popover.querySelector('[data-cursel-empty]');

    if (!box || rows.length === 0) return;

    // Only useful with JS, so it stays hidden until there is some.
    box.hidden = false;

    on(box, 'input', () => {
        const term = box.value.trim().toLowerCase();
        let shown = 0;

        rows.forEach((row) => {
            // Matches name, ISO code and currency, so "LKR", "LK" and
            // "Sri" all find the same row.
            const hit = term === '' || (row.dataset.label ?? '').includes(term);
            row.hidden = !hit;
            if (hit) shown += 1;
        });

        if (empty) empty.hidden = shown > 0;
    });
}

export function initCurrencyPicker() {
    const host = $('[data-cursel]');
    if (!host) return;

    const button = host.querySelector('[data-cursel-btn]');
    const popover = host.querySelector('[data-cursel-pop], .cursel__pop');

    if (!button || !popover) return;
    if (host.dataset.curselReady === 'true') return;
    host.dataset.curselReady = 'true';

    let isOpen = false;
    let hideTimer;

    // As with the account popover: the CSS fades in on .is-in, so
    // unhiding alone leaves it at opacity 0 and unclickable.
    const setOpen = (open) => {
        isOpen = open;
        button.setAttribute('aria-expanded', String(open));
        clearTimeout(hideTimer);

        if (open) {
            popover.hidden = false;
            requestAnimationFrame(() => popover.classList.add('is-in'));
            popover.querySelector('[data-cursel-filter]')?.focus();
            return;
        }

        popover.classList.remove('is-in');
        hideTimer = setTimeout(() => {
            popover.hidden = true;
        }, FADE_MS);
    };

    on(button, 'click', (event) => {
        event.stopPropagation();
        setOpen(!isOpen);
    });

    initFilter(popover);

    onceGlobal('cursel-dismiss', () => {
        on(document, 'click', (event) => {
            const open = $('[data-cursel] .cursel__pop:not([hidden])');
            if (!open) return;

            const picker = open.closest('[data-cursel]');
            if (picker && !picker.contains(event.target)) {
                picker.querySelector('[data-cursel-btn]')?.click();
            }
        });

        on(document, 'keydown', (event) => {
            if (event.key !== 'Escape') return;

            const open = $('[data-cursel] .cursel__pop:not([hidden])');
            open?.closest('[data-cursel]')?.querySelector('[data-cursel-btn]')?.click();
        });
    });
}
