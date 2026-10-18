import { $, delegate, on, onceGlobal } from './dom.js';
import { store } from './storage.js';

/**
 * Command-palette search.
 *
 * Queries the server so results always reflect the real catalogue rather than
 * a copy of it shipped to the browser.
 */

const RECENT_KEY = 'recent.v1';
const MAX_RECENT = 5;
const DEBOUNCE_MS = 220;

function rememberQuery(query) {
    const recent = store.get(RECENT_KEY, []).filter((q) => q !== query);
    recent.unshift(query);
    store.set(RECENT_KEY, recent.slice(0, MAX_RECENT));
}

function renderRecent() {
    const host = $('#searchRecent');
    if (!host) return;

    const recent = store.get(RECENT_KEY, []);
    if (recent.length === 0) {
        host.innerHTML = '';
        return;
    }

    host.innerHTML = '<p class="lbl">Recent</p><div class="search__chips"></div>';
    const chips = host.querySelector('.search__chips');

    recent.forEach((query) => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'chip';
        chip.dataset.q = query;
        chip.textContent = query;
        chips.appendChild(chip);
    });
}

async function runSearch(query) {
    const list = $('#searchList');
    const popular = $('#searchPopular');
    if (!list) return;

    if (query.trim().length < 2) {
        list.innerHTML = '';
        popular?.removeAttribute('hidden');
        return;
    }

    popular?.setAttribute('hidden', '');
    list.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(`/search?q=${encodeURIComponent(query)}`, {
            headers: { Accept: 'application/json' },
        });
        const results = response.ok ? await response.json() : [];
        renderResults(results, query);
    } catch {
        list.innerHTML = '<p class="search__none">Search is unavailable right now.</p>';
    } finally {
        list.removeAttribute('aria-busy');
    }
}

function renderResults(results, query) {
    const list = $('#searchList');

    if (results.length === 0) {
        list.innerHTML = '<p class="search__none">No stones match that.</p>';
        return;
    }

    rememberQuery(query);
    list.innerHTML = '';

    results.forEach((result, index) => {
        const row = document.createElement('a');
        row.className = 'sresult';
        row.href = result.url;
        row.setAttribute('role', 'option');
        row.setAttribute('aria-selected', String(index === 0));
        row.innerHTML = `
            <span class="sresult__img plate"><img src="${result.image}" alt="" loading="lazy"></span>
            <span class="sresult__t"><b></b><small></small></span>
            <span class="sresult__p"></span>`;
        row.querySelector('b').textContent = result.name;
        row.querySelector('small').textContent = result.meta;
        row.querySelector('.sresult__p').textContent = result.price ?? '';
        list.appendChild(row);
    });
}

/** Arrow keys move the active option; Enter opens it. */
function moveSelection(step) {
    const options = [...document.querySelectorAll('#searchList .sresult')];
    if (options.length === 0) return;

    const current = options.findIndex((el) => el.getAttribute('aria-selected') === 'true');
    const next = (current + step + options.length) % options.length;

    options.forEach((el, i) => el.setAttribute('aria-selected', String(i === next)));
    options[next].scrollIntoView({ block: 'nearest' });
}

export function initSearch() {
    const input = $('#searchIn');
    if (!input) return;

    renderRecent();

    let timer;
    on(input, 'input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => runSearch(input.value), DEBOUNCE_MS);
    });

    on(input, 'keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            moveSelection(1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            moveSelection(-1);
        } else if (event.key === 'Enter') {
            const active = $('#searchList .sresult[aria-selected="true"]');
            if (active) {
                event.preventDefault();
                active.click();
            }
        }
    });

    onceGlobal('search-chips', () => delegate(document, 'click', '[data-q]', (event, chip) => {
        input.value = chip.dataset.q;
        runSearch(input.value);
        input.focus();
    }));
}
