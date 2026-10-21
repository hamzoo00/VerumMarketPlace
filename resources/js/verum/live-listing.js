import { $, onceGlobal } from './dom.js';

/**
 * One listing page, kept current.
 *
 * A seller editing the item — or changing their postage rates — rewrites
 * what this page shows. ListingChanged already broadcasts for both, so
 * the page re-reads itself and swaps the regions that can change.
 *
 * Refetching rather than patching keeps one definition of the delivery
 * block, the price and the specs: all on the server, in Blade.
 */

const SETTLE_MS = 350;

/** Regions replaced wholesale. Order does not matter; each is independent. */
const REGIONS = ['.lship', '.buy__price', '.buyacts', '.specs'];

async function refresh() {
    const response = await fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

    // A listing pulled from sale returns 404; leave the page alone rather
    // than blanking it under someone who is reading it.
    if (!response.ok) return;

    const fresh = new DOMParser().parseFromString(await response.text(), 'text/html');

    REGIONS.forEach((selector) => {
        const current = $(selector);
        const next = fresh.querySelector(selector);

        if (current && next) {
            current.replaceWith(next);
        }
    });

    document.dispatchEvent(new CustomEvent('verum:content-added', {
        detail: { root: document.body },
    }));
}

async function connect() {
    if (window.Echo) return window.Echo;

    await import('../echo.js');

    return window.Echo ?? null;
}

export function initListingLive() {
    const page = $('[data-live-listing]');
    if (!page) return;

    const slug = page.dataset.liveListing;
    if (!slug) return;

    onceGlobal('live-listing-page', async () => {
        const echo = await connect();
        if (!echo) return;

        let queued = null;

        echo.channel('listings').listen('.listing.changed', (payload) => {
            // The channel carries every listing; only this one matters.
            if (payload.slug !== slug) return;

            clearTimeout(queued);
            queued = setTimeout(refresh, SETTLE_MS);
        });
    });
}
