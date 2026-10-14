import { $, $$, delegate } from './dom.js';
import { store } from './storage.js';
import { toast } from './toasts.js';

/**
 * Local cart.
 *
 * Holds slugs only; prices and names are read from the server-rendered markup
 * so there is no second source of truth for money.
 */

const KEY = 'cart.v1';

const read = () => store.get(KEY, []);
const write = (slugs) => store.set(KEY, slugs);

function syncCounts(slugs) {
    const n = slugs.length;

    $$('[data-cart-count]').forEach((el) => {
        el.textContent = String(n);
        el.hidden = n === 0;
    });
}

function syncButtons(slugs) {
    $$('[data-add]').forEach((button) => {
        const inCart = slugs.includes(button.dataset.add);
        button.classList.toggle('is-in', inCart);

        const label = button.querySelector('span');
        if (label) {
            label.textContent = inCart ? 'In cart' : 'Add';
        }
    });
}

export function addToCart(slug) {
    const slugs = read();

    if (slugs.includes(slug)) {
        toast('Already in your cart');
        return;
    }

    slugs.push(slug);
    write(slugs);
    syncCounts(slugs);
    syncButtons(slugs);
    toast('Added to your cart', { icon: 'bag' });
}

export function removeFromCart(slug) {
    const slugs = read().filter((s) => s !== slug);
    write(slugs);
    syncCounts(slugs);
    syncButtons(slugs);
    renderCartPage(slugs);
}

/**
 * Cart page.
 *
 * Each stone's details are fetched as HTML from its own server-rendered page,
 * so the cart shows exactly what the listing shows.
 */
async function renderCartPage(slugs = read()) {
    const root = $('[data-cart-root]');
    if (!root) return;

    if (slugs.length === 0) {
        $('[data-cart-empty]', root)?.removeAttribute('hidden');
        return;
    }

    $('[data-cart-empty]', root)?.setAttribute('hidden', '');

    const rows = await Promise.all(slugs.map(fetchCartRow));
    const list = document.createElement('div');
    list.className = 'cartlist';
    rows.filter(Boolean).forEach((row) => list.appendChild(row));

    root.querySelector('.cartlist')?.remove();
    root.appendChild(list);
}

async function fetchCartRow(slug) {
    try {
        const response = await fetch(`/stones/${slug}`, {
            headers: { 'X-Requested-With': 'fetch' },
        });
        if (!response.ok) return null;

        const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
        const name = doc.querySelector('.buy__title')?.textContent.trim() ?? slug;
        const price = doc.querySelector('.buy__price')?.firstChild?.textContent.trim() ?? '';
        const image = doc.querySelector('#stageImg')?.getAttribute('src') ?? '';

        const row = document.createElement('div');
        row.className = 'cartrow';
        row.innerHTML = `
            <a class="cartrow__img plate" href="/stones/${slug}"><img src="${image}" alt="" loading="lazy"></a>
            <div class="cartrow__t"><b></b><span></span></div>
            <button class="ibtn" type="button" data-remove="${slug}" aria-label="Remove">
                <svg class="ic"><use href="#i-trash"/></svg>
            </button>`;
        row.querySelector('b').textContent = name;
        row.querySelector('span').textContent = price;

        return row;
    } catch {
        return null;
    }
}

export function initCart() {
    const slugs = read();
    syncCounts(slugs);
    syncButtons(slugs);
    renderCartPage(slugs);

    delegate(document, 'click', '[data-add]', (event, button) => {
        event.preventDefault();
        addToCart(button.dataset.add);
    });

    delegate(document, 'click', '[data-remove]', (event, button) => {
        event.preventDefault();
        removeFromCart(button.dataset.remove);
    });
}
