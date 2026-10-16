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
            const dy = y - lastY;

            // Must match .hdr.is-scrolled in css/verum/header.css, which paints
            // the background so content stays readable behind the header.
            header.classList.toggle('is-scrolled', y > 30);

            // Hide going down, reveal going up. The 4px threshold stops the
            // header flickering on sub-pixel and momentum scrolls.
            if (y > 280 && dy > 4) {
                header.classList.add('is-hidden');
            } else if (dy < -4 || y < 280) {
                header.classList.remove('is-hidden');
            }

            lastY = y;
        },
        { passive: true },
    );
}

function initAccountPopover() {
    const button = $('#acctBtn');
    const popover = $('#acctPop');
    if (!button || !popover) return;

    let isOpen = false;
    let hideTimer;

    // The CSS fades the panel in on .is-in, so unhiding alone leaves it at
    // opacity 0 — invisible and unclickable.
    const setOpen = (open) => {
        isOpen = open;
        button.setAttribute('aria-expanded', String(open));
        clearTimeout(hideTimer);

        if (open) {
            popover.hidden = false;
            // Next frame, so the transition has a start state to run from.
            requestAnimationFrame(() => popover.classList.add('is-in'));
            return;
        }

        popover.classList.remove('is-in');
        // Keep it in the tree until the fade-out finishes.
        hideTimer = setTimeout(() => {
            popover.hidden = true;
        }, 300);
    };

    on(button, 'click', (event) => {
        event.stopPropagation();
        setOpen(!isOpen);
    });

    on(document, 'click', (event) => {
        if (isOpen && !popover.contains(event.target) && !button.contains(event.target)) {
            setOpen(false);
        }
    });

    on(document, 'keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            setOpen(false);
            button.focus();
        }
    });
}

function initMobileMenu() {
    const burger = $('#burger');
    const menu = $('#mnav');
    if (!burger || !menu) return;

    const setOpen = (open) => {
        // A hidden header would clip the open menu.
        if (open) $('#hdr')?.classList.remove('is-hidden');

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
