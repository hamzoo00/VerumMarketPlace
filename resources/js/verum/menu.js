import { $, $$, on, delegate } from './dom.js';

/**
 * Three-dot action menus.
 *
 * Each item is a real form, so everything still works with JavaScript off —
 * this only adds show/hide, keyboard handling, and a confirm on the
 * destructive action.
 */

/** Document-level handlers are global; bind them once per page load. */
let globalsBound = false;

function closeAll(except = null) {
    $$('[data-menu]').forEach((menu) => {
        if (menu === except) return;

        const pop = $('[role="menu"]', menu);
        const toggle = $('[data-menu-toggle]', menu);
        if (pop) pop.hidden = true;
        toggle?.setAttribute('aria-expanded', 'false');
    });
}

function bindGlobals() {
    if (globalsBound) return;
    globalsBound = true;

    on(document, 'click', (event) => {
        if (!event.target.closest('[data-menu]')) closeAll();
    });

    on(document, 'keydown', (event) => {
        if (event.key !== 'Escape') return;

        const open = $$('[data-menu] [role="menu"]:not([hidden])')[0];
        if (!open) return;

        closeAll();
        open.closest('[data-menu]')?.querySelector('[data-menu-toggle]')?.focus();
    });

    // Destructive actions ask first.
    delegate(document, 'submit', 'form[data-confirm]', (event, form) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
}

export function initMenus() {
    bindGlobals();

    $$('[data-menu]').forEach((menu) => {
        // boot() re-runs after every wire:navigate. Without this guard the
        // toggle collects a second listener, so one click opens and closes
        // the menu and it looks dead.
        if (menu.dataset.menuReady === 'true') return;

        const toggle = $('[data-menu-toggle]', menu);
        const pop = $('[role="menu"]', menu);
        if (!toggle || !pop) return;

        menu.dataset.menuReady = 'true';

        on(toggle, 'click', (event) => {
            event.stopPropagation();

            const willOpen = pop.hidden;
            closeAll(menu);
            pop.hidden = !willOpen;
            toggle.setAttribute('aria-expanded', String(willOpen));

            if (willOpen) {
                $('[role="menuitem"]', pop)?.focus();
            }
        });
    });
}
