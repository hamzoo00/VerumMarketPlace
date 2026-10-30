import { $$, onceGlobal } from './dom.js';

/**
 * Parts of a page that keep themselves current.
 *
 * Any element marked data-live-region="name" is replaced wholesale with
 * the same region from a fresh copy of the page whenever something says
 * the data behind it changed — a live form succeeding, a notification
 * arriving, another admin's decision. The server renders the new state;
 * the browser never patches a row or a count itself, the same rule
 * live-seller.js and live-chat.js follow, so there is one definition of
 * every list: the Blade that drew it.
 */

const SETTLE_MS = 180;

let timer = null;
let inflight = null;

/**
 * Someone is working in this region: typing in it, or holding a form
 * with unsent changes. Another admin's save must not wipe that — the
 * region catches up at the next refresh after they are done.
 */
function isBusy(region) {
    const active = document.activeElement;

    if (active && region.contains(active) && active.matches('input, textarea, select')) {
        return true;
    }

    return region.querySelector('form[data-live-dirty]') !== null;
}

export async function refreshRegions() {
    if ($$('[data-live-region]').length === 0) return;

    // A newer refresh makes an older one pointless.
    inflight?.abort();
    const controller = new AbortController();
    inflight = controller;

    let html;

    try {
        const response = await fetch(window.location.href, {
            // text/html, so the server renders the page rather than JSON.
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            credentials: 'same-origin',
            signal: controller.signal,
        });

        if (!response.ok) return;

        html = await response.text();
    } catch {
        return;
    } finally {
        if (inflight === controller) inflight = null;
    }

    const fresh = new DOMParser().parseFromString(html, 'text/html');

    $$('[data-live-region]').forEach((region) => {
        const name = CSS.escape(region.dataset.liveRegion ?? '');
        const next = fresh.querySelector(`[data-live-region="${name}"]`);

        if (next && !isBusy(region)) region.replaceWith(next);
    });

    // The swapped-in markup was never seen by the observers set at boot.
    document.dispatchEvent(new CustomEvent('verum:content-added', {
        detail: { root: document.body },
    }));
}

/** Several signals often arrive together; answer them once. */
export function scheduleRefresh() {
    clearTimeout(timer);
    timer = setTimeout(refreshRegions, SETTLE_MS);
}

export function initLiveRegions() {
    onceGlobal('live-regions', () => {
        document.addEventListener('verum:live-refresh', scheduleRefresh);
    });
}
