import { $, $$, onceGlobal } from './dom.js';

/**
 * Small form behaviour for offers and coupons.
 *
 * Both are the same idea: a control decides which fields still apply, so
 * the ones that do not are hidden rather than left to confuse. The server
 * clears them regardless.
 */

function syncOfferFields() {
    const toggle = $('[data-offers-toggle]');
    const fields = $('[data-offers-fields]');

    if (!toggle || !fields) return;

    fields.hidden = !toggle.checked;
}

function syncCouponFields() {
    const form = $('[data-coupon-form]');
    if (!form) return;

    const type = form.querySelector('[name="type"]')?.value;

    $$('[data-coupon-when]', form).forEach((field) => {
        // The wrapper carries the label and hint, not just the input.
        const wrapper = field.closest('.field') ?? field;
        wrapper.hidden = field.dataset.couponWhen !== type;
    });
}

export function initOfferForms() {
    syncOfferFields();
    syncCouponFields();

    onceGlobal('offer-forms', () => {
        document.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;

            if (target.matches('[data-offers-toggle]')) syncOfferFields();
            if (target.matches('[data-coupon-form] [name="type"]')) syncCouponFields();
        });
    });
}
