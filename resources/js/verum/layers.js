import { $, $$, on, delegate } from './dom.js';

/**
 * Search palette, drawers and modals.
 *
 * Each layer traps focus while open, restores it on close, and locks body
 * scroll so the page behind cannot be scrolled away.
 */

let openLayer = null;
let lastFocused = null;

const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select,textarea,[tabindex]:not([tabindex="-1"])';

function lockScroll(locked) {
    document.body.style.overflow = locked ? 'hidden' : '';
}

export function openLayerEl(el, { focus } = {}) {
    if (!el || openLayer === el) return;

    closeLayer();
    lastFocused = document.activeElement;
    openLayer = el;

    el.hidden = false;
    lockScroll(true);
    requestAnimationFrame(() => el.classList.add('is-open'));

    const target = focus ? $(focus, el) : $(FOCUSABLE, el);
    target?.focus();
}

export function closeLayer() {
    if (!openLayer) return;

    const el = openLayer;
    openLayer = null;

    el.classList.remove('is-open');
    lockScroll(false);

    const finish = () => {
        el.hidden = true;
    };
    el.addEventListener('transitionend', finish, { once: true });
    setTimeout(finish, 320);

    lastFocused?.focus();
    lastFocused = null;
}

/** Keeps Tab cycling inside the open layer. */
function trapFocus(event) {
    if (!openLayer || event.key !== 'Tab') return;

    const items = $$(FOCUSABLE, openLayer).filter((el) => el.offsetParent !== null);
    if (items.length === 0) return;

    const first = items[0];
    const last = items[items.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

export function initLayers() {
    const triggers = [
        ['[data-open-search]', '#search', '#searchIn'],
        ['[data-open-wish]', '#wish', null],
        ['[data-open-filters]', '#filters', null],
    ];

    triggers.forEach(([trigger, layer, focus]) => {
        delegate(document, 'click', trigger, () => {
            openLayerEl($(layer), focus ? { focus } : {});
        });
    });

    [
        '[data-close-search]',
        '[data-close-wish]',
        '[data-close-modal]',
        '[data-close-filters]',
    ].forEach((selector) => delegate(document, 'click', selector, closeLayer));

    on(document, 'keydown', (event) => {
        if (event.key === 'Escape') {
            closeLayer();
            return;
        }

        // Ctrl/Cmd+K opens the search palette from anywhere.
        if (event.key.toLowerCase() === 'k' && (event.metaKey || event.ctrlKey)) {
            event.preventDefault();
            openLayerEl($('#search'), { focus: '#searchIn' });
            return;
        }

        trapFocus(event);
    });
}
