import { $, onceGlobal } from './dom.js';

/**
 * Live catalogue.
 *
 * The server broadcasts a rendered card whenever a listing appears, changes
 * or leaves. Nothing is composed in the browser — the markup arriving over
 * the socket is the same Blade component the page was rendered with, so a
 * live-inserted card is identical to a reloaded one.
 *
 * A grid declares what it is showing via data attributes, and only accepts
 * listings that match: a jewellery item must not appear in a stones filter.
 */

const CHANNEL = 'listings';

/** Does this listing belong in this grid, given its active filters? */
function matches(grid, payload) {
    const want = grid.dataset;

    if (want.liveKind && want.liveKind !== payload.kind) return false;
    if (want.liveType && want.liveType !== payload.type) return false;
    if (want.liveUntreated === '1' && !payload.untreated) return false;

    // A chosen family is a branch: the controller resolves descendants, and
    // the browser cannot, so only an exact match is safe to insert.
    if (want.liveCategory && want.liveCategory !== payload.category) return false;

    return true;
}

function cardFor(grid, slug) {
    return grid.querySelector(`[data-live-slug="${CSS.escape(slug)}"]`);
}

function adjustCount(delta) {
    const el = $('[data-live-count]');
    if (!el || delta === 0) return;

    const next = Math.max(0, Number(el.textContent.trim() || 0) + delta);
    el.textContent = String(next);
}

/** Replace in place when already shown, otherwise lead with it. */
function put(grid, payload) {
    if (!payload.card) return;

    const holder = document.createElement('div');
    holder.innerHTML = payload.card.trim();

    const fresh = holder.firstElementChild;
    if (!fresh) return;

    fresh.dataset.liveSlug = payload.slug;

    // The card carries [data-reveal], which starts it faded and shifted
    // down. initReveal() only observes what existed at boot, so without
    // this a live card stays stuck at its pre-animation offset — it looks
    // dislocated rather than new.
    fresh.classList.add('is-in', 'is-live-in');

    const existing = cardFor(grid, payload.slug);

    if (existing) {
        existing.replaceWith(fresh);
    } else {
        grid.prepend(fresh);
        adjustCount(1);

        // An empty grid is still showing its "nothing matches" panel.
        $('[data-live-empty]')?.remove();
    }

    // Re-arm rotating imagery, countdowns and reveals on the new card.
    document.dispatchEvent(new CustomEvent('verum:content-added', { detail: { root: fresh } }));
}

function drop(grid, payload) {
    const existing = cardFor(grid, payload.slug);
    if (!existing) return;

    existing.classList.add('is-live-out');
    adjustCount(-1);

    const remove = () => existing.remove();
    existing.addEventListener('transitionend', remove, { once: true });
    setTimeout(remove, 500);
}

/**
 * Echo pulls in a WebSocket client worth ~90KB gzipped. Only catalogue
 * pages need it, so it is fetched on demand rather than in the main bundle.
 */
async function connect() {
    if (window.Echo) return window.Echo;

    await import('../echo.js');

    return window.Echo ?? null;
}

/**
 * A Livewire-owned grid is reconciled against server state on every round
 * trip, so a card patched in by hand is discarded at the next morph. There
 * the socket's job is to say "something changed" and let the component
 * re-read the database — which also keeps the count, the ordering and the
 * "load more" state honest, none of which the browser can work out alone.
 */
function livewireGrid(grid) {
    return grid.closest('[wire\\:id]');
}

/** Coalesce a burst of edits into one refresh. */
function refresher(componentEl) {
    let queued = null;

    return () => {
        if (queued !== null) return;

        queued = setTimeout(() => {
            queued = null;
            window.Livewire?.find(componentEl.getAttribute('wire:id'))?.$refresh();
        }, 250);
    };
}

export function initLive() {
    if (!$('[data-live-grid]')) return;

    // One subscription per page load: boot() re-runs on every Livewire
    // navigation, and a second listener would insert each card twice.
    onceGlobal('live-listings', async () => {
        const echo = await connect();
        if (!echo) return;

        echo.channel(CHANNEL).listen('.listing.changed', (payload) => {
            // Re-read the grid each time: navigation replaces the element.
            const current = $('[data-live-grid]');
            if (!current) return;

            const component = livewireGrid(current);

            if (component) {
                // Only disturb the page for a listing it could be showing.
                if (payload.action === 'put' && !matches(current, payload)) {
                    if (!cardFor(current, payload.slug)) return;
                }

                (component._verumRefresh ??= refresher(component))();

                return;
            }

            if (payload.action === 'put' && matches(current, payload)) {
                put(current, payload);
            } else {
                drop(current, payload);
            }
        });
    });
}
