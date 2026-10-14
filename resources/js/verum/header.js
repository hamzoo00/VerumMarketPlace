import { $, on, delegate } from './dom.js';

/** Sticky header state, account popover and the mobile menu. */

function initStickyHeader() {
    const header = $('#hdr');
    if (!header) return;

    let lastY = window.scrollY;

    on(
        window,
        'scroll',
        () => {
            const y = window.scrollY;
            header.classList.toggle('is-stuck', y > 24);
            // Hide on the way down, reveal on the way up.
            header.classList.toggle('is-hidden', y > lastY && y > 320);
            lastY = y;
        },
        { passive: true },
    );
}

function initAccountPopover() {
    const button = $('#acctBtn');
    const popover = $('#acctPop');
    if (!button || !popover) return;

    const setOpen = (open) => {
        popover.hidden = !open;
        button.setAttribute('aria-expanded', String(open));
    };

    on(button, 'click', (event) => {
        event.stopPropagation();
        setOpen(popover.hidden);
    });

    on(document, 'click', (event) => {
        if (!popover.hidden && !popover.contains(event.target) && event.target !== button) {
            setOpen(false);
        }
    });

    on(document, 'keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });
}

function initMobileMenu() {
    const burger = $('#burger');
    const menu = $('#mnav');
    if (!burger || !menu) return;

    const setOpen = (open) => {
        menu.classList.toggle('is-open', open);
        menu.setAttribute('aria-hidden', String(!open));
        burger.setAttribute('aria-expanded', String(open));
        burger.classList.toggle('is-x', open);
        document.body.style.overflow = open ? 'hidden' : '';
    };

    on(burger, 'click', () => setOpen(!menu.classList.contains('is-open')));

    // Close when a destination is chosen.
    delegate(menu, 'click', 'a', () => setOpen(false));

    on(document, 'keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });
}

export function initHeader() {
    initStickyHeader();
    initAccountPopover();
    initMobileMenu();
}
