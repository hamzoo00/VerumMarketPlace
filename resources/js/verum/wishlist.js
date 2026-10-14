import { $, $$, delegate } from './dom.js';
import { store } from './storage.js';
import { toast } from './toasts.js';

/**
 * Saved stones.
 *
 * Per-device only until buyer accounts exist; the server never sees this.
 */

const KEY = 'wish.v1';

const read = () => store.get(KEY, []);
const write = (ids) => store.set(KEY, ids);

function syncCounts(ids) {
    const n = ids.length;

    $$('[data-wish-count]').forEach((el) => {
        el.textContent = String(n);
        el.hidden = n === 0;
    });

    $$('[data-wish-count2]').forEach((el) => {
        el.textContent = String(n);
    });
}

/** Marks every save button on the page for the stones currently saved. */
function syncButtons(ids) {
    $$('[data-wish]').forEach((button) => {
        const on = ids.includes(button.dataset.wish);
        button.classList.toggle('is-on', on);
        button.setAttribute('aria-pressed', String(on));
    });
}

export function toggleWish(slug) {
    const ids = read();
    const index = ids.indexOf(slug);
    const adding = index === -1;

    if (adding) {
        ids.push(slug);
    } else {
        ids.splice(index, 1);
    }

    write(ids);
    syncCounts(ids);
    syncButtons(ids);
    renderDrawer(ids);

    toast(adding ? 'Saved to your stones' : 'Removed from saved', {
        icon: adding ? 'heart' : 'check',
    });
}

/**
 * The drawer lists saved stones by reusing the card markup already on the
 * page, so there is no client-side template to keep in sync with Blade.
 */
function renderDrawer(ids = read()) {
    const body = $('#wishBody');
    if (!body) return;

    if (ids.length === 0) {
        body.innerHTML =
            '<div class="empty"><h3>No saved stones yet.</h3>' +
            '<p>Tap the heart on any stone to keep it here.</p></div>';
        return;
    }

    const list = document.createElement('div');
    list.className = 'wishlist';

    ids.forEach((slug) => {
        const source = $(`[data-wish="${slug}"]`)?.closest('.scard');
        if (!source) return;

        const name = source.querySelector('.scard__name')?.textContent.trim() ?? slug;
        const price = source.querySelector('.scard__price')?.textContent.trim() ?? '';
        const href = source.querySelector('.scard__name a')?.getAttribute('href') ?? '#';
        const image = source.querySelector('img')?.getAttribute('src') ?? '';

        const row = document.createElement('a');
        row.className = 'wishrow';
        row.href = href;
        row.innerHTML = `
            <span class="wishrow__img plate"><img src="${image}" alt="" loading="lazy"></span>
            <span class="wishrow__t"><b></b><small></small></span>`;
        row.querySelector('b').textContent = name;
        row.querySelector('small').textContent = price;

        list.appendChild(row);
    });

    body.innerHTML = '';
    body.appendChild(list);
}

export function initWishlist() {
    const ids = read();
    syncCounts(ids);
    syncButtons(ids);
    renderDrawer(ids);

    delegate(document, 'click', '[data-wish]', (event, button) => {
        event.preventDefault();
        toggleWish(button.dataset.wish);
    });
}
