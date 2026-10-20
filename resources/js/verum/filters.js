import { onceGlobal } from './dom.js';

/**
 * Shop filters.
 *
 * The sidebar's submit button only exists on narrow screens, where filters
 * open as a sheet. On desktop the panel is always visible and has no button,
 * so a choice has to apply itself — otherwise the controls look inert.
 *
 * The form is still a plain GET form: without JS the mobile button submits
 * it, and every filtered view keeps a shareable URL.
 */

function submit(form) {
    // Page back to the first result set: a filter changes what page 2 means.
    form.querySelector('input[name="page"]')?.remove();

    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
    } else {
        form.submit();
    }
}

export function initFilters() {
    onceGlobal('filters-apply', () => {
        document.addEventListener('change', (event) => {
            const input = event.target;
            if (!(input instanceof HTMLInputElement)) return;

            const form = input.closest('[data-filter-form]');
            if (!form) return;

            // Choosing a branch changes which families exist, so the stale
            // family must not travel with the request.
            if (input.name === 'kind') {
                form.querySelectorAll('input[name="category"]').forEach((el) => {
                    el.checked = el.value === '';
                });
            }

            submit(form);
        });
    });
}
