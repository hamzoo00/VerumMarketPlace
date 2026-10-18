import { $, $$, on } from './dom.js';

/**
 * Keeps the dialling code in step with the chosen country.
 *
 * The code is pre-filled, never locked: a seller trading from Pakistan may
 * still carry a UK number. Once they pick a code by hand we stop overwriting
 * it, so their choice is not undone by a later country change.
 */
export function initPhoneFields() {
    const fields = $$('[data-phone-field]');
    if (fields.length === 0) return;

    fields.forEach((field) => {
        const dial = $('[data-phone-dial]', field);
        const country = document.getElementById(field.dataset.countrySource ?? '');
        if (!dial || !country) return;

        // Flag a manual change so we stop syncing from the country.
        on(dial, 'change', () => {
            dial.dataset.touched = 'true';
        });

        const sync = () => {
            if (dial.dataset.touched === 'true') return;

            const code = country.selectedOptions[0]?.dataset.dial;
            if (code && [...dial.options].some((o) => o.value === code)) {
                dial.value = code;
            }
        };

        on(country, 'change', sync);

        // Only seed an untouched field, so a validation redisplay keeps
        // whatever the person actually submitted.
        if (dial.value === '' || !dial.value) sync();
    });
}
