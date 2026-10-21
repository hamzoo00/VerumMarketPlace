import { $, $$, delegate, onceGlobal } from './dom.js';

/**
 * The seller's postage rate table.
 *
 * Rows are cloned from a <template> in the markup rather than built here,
 * so the fields are defined in one place and stay in step with the Blade
 * component. The form posts the whole table, so removing a row is just
 * removing it from the DOM.
 */

/** Rows are addressed by position, so they must be renumbered after a change. */
function renumber(container) {
    $$('[data-rate-row]', container).forEach((row, index) => {
        // Names use brackets (rates[0][price]) because that is what HTML
        // posts as an array; ids and labels use dots, matching how the
        // validator and old() address the same field.
        $$('[name^="rates["], [id^="f-rates."]', row).forEach((field) => {
            if (field.name) {
                field.name = field.name.replace(/^rates\[\d+\]/, `rates[${index}]`);
            }

            if (field.id) {
                field.id = field.id.replace(/^f-rates\.\d+\./, `f-rates.${index}.`);
            }
        });

        $$('label[for^="f-rates."]', row).forEach((label) => {
            label.htmlFor = label.htmlFor.replace(/^f-rates\.\d+\./, `f-rates.${index}.`);
        });
    });
}

function syncEmptyState(container) {
    const empty = $('[data-rates-empty]');
    if (!empty) return;

    empty.hidden = $$('[data-rate-row]', container).length > 0;
}

function atLimit(container) {
    const max = Number(container.dataset.max || 0);

    return max > 0 && $$('[data-rate-row]', container).length >= max;
}

/**
 * The per-listing override: the flat-price box only matters when the
 * seller picked "one flat price", so it is hidden otherwise.
 */
function initListingOverride() {
    const box = $('[data-shipping-custom]');
    if (!box) return;

    onceGlobal('shipping-mode-toggle', () => {
        document.addEventListener('change', (event) => {
            const input = event.target;
            if (!(input instanceof HTMLInputElement) || input.name !== 'shipping_mode') return;

            const target = $('[data-shipping-custom]');
            if (target) target.hidden = input.value !== 'custom';
        });
    });
}

export function initShipping() {
    initListingOverride();

    const container = $('[data-rates]');
    if (!container) return;

    const template = $('[data-rate-template]');
    const addButton = $('[data-add-rate]');

    const refresh = () => {
        renumber(container);
        syncEmptyState(container);

        if (addButton) {
            addButton.disabled = atLimit(container);
        }
    };

    if (addButton && template && addButton.dataset.rateReady !== 'true') {
        addButton.dataset.rateReady = 'true';

        addButton.addEventListener('click', () => {
            if (atLimit(container)) return;

            const index = $$('[data-rate-row]', container).length;
            const markup = template.innerHTML.replaceAll('__INDEX__', String(index));

            container.insertAdjacentHTML('beforeend', markup);
            refresh();

            // Land the cursor in the new row rather than leaving the
            // seller to find where it went.
            $$('[data-rate-row]', container).at(-1)?.querySelector('select')?.focus();
        });
    }

    onceGlobal('shipping-remove-rate', () => {
        delegate(document, 'click', '[data-remove-rate]', (event, button) => {
            const host = button.closest('[data-rates]');
            const row = button.closest('[data-rate-row]');
            if (!host || !row) return;

            row.remove();
            renumber(host);
            syncEmptyState(host);

            const add = $('[data-add-rate]');
            if (add) add.disabled = atLimit(host);
        });
    });

    refresh();
}
