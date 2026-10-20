import { $$, on } from './dom.js';

/**
 * Shows the price fields that belong to the chosen listing type.
 *
 * Both sets stay in the DOM so a validation redisplay keeps what was typed;
 * the hidden set is disabled so it cannot submit values the seller can no
 * longer see.
 */
export function initPricing() {
    $$('[data-pricing]').forEach((fieldset) => {
        if (fieldset.dataset.pricingReady === 'true') return;

        const source = document.getElementById(fieldset.dataset.typeSource ?? '');
        const sets = $$('[data-pricing-set]', fieldset);
        if (!source || sets.length === 0) return;

        fieldset.dataset.pricingReady = 'true';

        const apply = () => {
            sets.forEach((set) => {
                const show = set.dataset.pricingSet === source.value;
                set.hidden = !show;

                $$('input, select', set).forEach((field) => {
                    field.disabled = !show;
                });
            });
        };

        on(source, 'change', apply);
        apply();
    });
}
