import { $, onceGlobal } from './dom.js';
import { connect } from './realtime.js';

/**
 * The seller's own workspace, kept current.
 *
 * ListingChanged already broadcasts on seller.{id}.listings for every
 * write path. Rather than patch numbers in the browser — which would
 * guess at what "live" or "draft" means and drift from the server — this
 * re-fetches the page and swaps in the parts that changed.
 *
 * That keeps one definition of every count: SellerStats, on the server.
 */

const SETTLE_MS = 400;

/** Regions that are replaced wholesale when inventory changes. */
const REGIONS = ['.astats', '.apanels', '[data-inventory]'];

async function refresh() {
    const response = await fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

    if (!response.ok) return;

    const fresh = new DOMParser()
        .parseFromString(await response.text(), 'text/html');

    REGIONS.forEach((selector) => {
        const current = $(selector);
        const next = fresh.querySelector(selector);

        if (current && next) {
            current.replaceWith(next);
        }
    });

    // The swapped-in markup was never seen by the observers set at boot.
    document.dispatchEvent(new CustomEvent('verum:content-added', {
        detail: { root: document.body },
    }));
}

export function initSellerLive() {
    const host = $('[data-seller-live]');
    if (!host) return;

    const sellerId = host.dataset.sellerLive;
    if (!sellerId) return;

    onceGlobal('live-seller-workspace', async () => {
        let queued = null;

        // A single save can fire more than once (the row, then its
        // media), so settle before asking the server again.
        const settle = () => {
            clearTimeout(queued);
            queued = setTimeout(refresh, SETTLE_MS);
        };

        // An admin's decision arrives as a notification — an approval, a
        // suspension — and the chips on this page move with it.
        document.addEventListener('verum:live-refresh', settle);

        const echo = await connect();
        if (!echo) return;

        echo.channel(`seller.${sellerId}.listings`).listen('.listing.changed', settle);
    });
}
