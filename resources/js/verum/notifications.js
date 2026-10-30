import { $, $$, on, onceGlobal } from './dom.js';
import { connect } from './realtime.js';
import { toast } from './toast.js';

/**
 * The bell: its panel, and keeping it current without a reload.
 *
 * Each account listens on its own private channel (authorised per guard
 * in routes/channels.php). When something arrives the bell re-reads
 * itself from the server — count, list, "mark all" — rather than adding
 * a row in the browser, and every live region on the page is told to do
 * the same, so a seller's suspension banner or a buyer's cancelled order
 * appears where they are already looking.
 *
 * Staff also listen on the shared staff channel, which names the admin
 * areas another admin just changed; pages showing one re-read it.
 */

/** The parts swapped on refresh. The panel itself stays, so an open bell stays open. */
const PARTS = ['[data-bell-count]', '[data-bell-list]', '[data-bell-readall]'];

let subscribed = null;
let staffBound = false;

function unreadIn(bell) {
    const count = $('[data-bell-count]', bell);

    return count && !count.hidden ? Number.parseInt(count.textContent, 10) || 0 : 0;
}

async function refreshBell() {
    const bell = $('[data-bell]');
    if (!bell?.dataset.bellSrc) return;

    try {
        const response = await fetch(bell.dataset.bellSrc, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            credentials: 'same-origin',
        });

        if (!response.ok) return;

        const fresh = new DOMParser().parseFromString(await response.text(), 'text/html');
        const before = unreadIn(bell);

        PARTS.forEach((selector) => {
            const current = $(selector, bell);
            const next = fresh.querySelector(selector);

            if (current && next) current.replaceWith(next);
        });

        // Rings once, and only when something new arrived — not on every
        // refresh, or the bell would twitch each time it is opened.
        if (unreadIn(bell) > before) $('[data-bell-count]', bell)?.classList.add('bump');

        const label = fresh.querySelector('[data-bell-toggle]')?.getAttribute('aria-label');
        if (label) $('[data-bell-toggle]', bell)?.setAttribute('aria-label', label);
    } catch {
        // A missed refresh is caught up by the next one, or the next page.
    }
}

function setOpen(bell, open) {
    const panel = $('[data-bell-panel]', bell);
    const toggle = $('[data-bell-toggle]', bell);
    if (!panel || !toggle) return;

    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    bell.classList.toggle('is-open', open);
}

function bindPanel(bell) {
    if (bell.dataset.bellReady === 'true') return;
    bell.dataset.bellReady = 'true';

    on($('[data-bell-toggle]', bell), 'click', (event) => {
        event.stopPropagation();

        const willOpen = $('[data-bell-panel]', bell)?.hidden ?? false;
        setOpen(bell, willOpen);

        // Opening is when "time ago" should be true.
        if (willOpen) refreshBell();
    });
}

function bindDismissal() {
    onceGlobal('bell-dismiss', () => {
        on(document, 'click', (event) => {
            const bell = $('[data-bell]');
            if (bell && !bell.contains(event.target)) setOpen(bell, false);
        });

        on(document, 'keydown', (event) => {
            const bell = $('[data-bell]');
            if (event.key !== 'Escape' || !bell?.classList.contains('is-open')) return;

            setOpen(bell, false);
            $('[data-bell-toggle]', bell)?.focus();
        });

        // "Mark all as read" and other live forms change the count too.
        document.addEventListener('verum:live-refresh', (event) => {
            if (event.detail?.source !== 'notification') refreshBell();
        });
    });
}

async function listen(bell) {
    const channel = bell.dataset.bellChannel;
    if (!channel || channel === subscribed) return;

    const echo = await connect();
    if (!echo) return;

    // Navigating between areas can change whose bell this is.
    if (subscribed) echo.leave(subscribed);
    subscribed = channel;

    echo.private(channel).notification((payload) => {
        refreshBell();

        if (payload?.title) toast(payload.title, 'info');

        document.dispatchEvent(new CustomEvent('verum:live-refresh', { detail: { source: 'notification' } }));
    });

    if (bell.hasAttribute('data-bell-staff') && !staffBound) {
        staffBound = true;

        echo.private('staff').listen('.staff.changed', ({ areas = [] } = {}) => {
            const watching = $$('[data-staff-area]').some((region) => areas.includes(region.dataset.staffArea));

            if (watching) {
                document.dispatchEvent(new CustomEvent('verum:live-refresh', { detail: { source: 'staff' } }));
            }
        });
    }
}

export function initNotifications() {
    const bell = $('[data-bell]');
    if (!bell) return;

    bindPanel(bell);
    bindDismissal();
    listen(bell);
}
