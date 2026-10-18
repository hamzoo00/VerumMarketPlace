import { $$, on } from './dom.js';

/**
 * Shows only the specialty groups that belong to the chosen category.
 *
 * Every group is in the markup already, so with JavaScript off the seller
 * simply sees all of them — the form still works, it is just less tidy.
 */
export function initSpecialties() {
    const fieldsets = $$('[data-specialties]');
    if (fieldsets.length === 0) return;

    fieldsets.forEach((fieldset) => {
        const source = document.getElementById(fieldset.dataset.categorySource ?? '');
        const groups = $$('[data-specialty-group]', fieldset);
        if (!source || groups.length === 0) return;

        const apply = () => {
            const chosen = source.value;

            groups.forEach((group) => {
                const key = group.dataset.specialtyGroup;
                // "both" shows everything; an empty choice shows nothing yet.
                const show = chosen === 'both' || chosen === key;

                group.hidden = !show;

                // A hidden checkbox must not submit a value the seller
                // cannot see or uncheck.
                $$('input[type="checkbox"]', group).forEach((box) => {
                    box.disabled = !show;
                    if (!show) box.checked = false;
                });
            });

            fieldset.hidden = chosen === '';
        };

        on(source, 'change', apply);
        apply();
    });
}
