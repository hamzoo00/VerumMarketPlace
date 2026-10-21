import { $, onceGlobal } from './dom.js';

/**
 * Offers, live for both sides.
 *
 * A negotiation only works if the other party sees the reply without
 * refreshing. The channel is **private** — Echo authorises against the
 * signed-in guard, so a buyer cannot subscribe to a seller's offers even
 * by guessing the id.
 *
 * As elsewhere, the page refetches rather than patching: the row's state,
 * whose turn it is and the expiry countdown are all decided server-side.
 */

const SETTLE_MS = 300;

async function refresh() {
    const response = await fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

    if (!response.ok) return;

    const fresh = new DOMParser().parseFromString(await response.text(), 'text/html');

    const current = $('[data-offers-live]');
    const next = fresh.querySelector('[data-offers-live]');

    if (current && next) {
        current.replaceWith(next);

        document.dispatchEvent(new CustomEvent('verum:content-added', {
            detail: { root: document.body },
        }));
    }
}

async function connect() {
    if (window.Echo) return window.Echo;

    await import('../echo.js');

    return window.Echo ?? null;
}

export function initOfferLive() {
    const host = $('[data-offers-live]');
    if (!host) return;

    const channel = host.dataset.offersLive;
    if (!channel) return;

    onceGlobal('live-offers', async () => {
        const echo = await connect();
        if (!echo) return;

        let queued = null;

        // `private` so the server authorises the subscription; see
        // routes/channels.php.
        echo.private(channel).listen('.offer.changed', () => {
            clearTimeout(queued);
            queued = setTimeout(refresh, SETTLE_MS);
        });
    });
}
