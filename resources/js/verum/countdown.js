import { $$ } from './dom.js';

/**
 * Auction countdowns.
 *
 * The server renders the correct remaining time into each <time datetime>,
 * so this only keeps it ticking — the first paint is already accurate.
 */

const UNITS = [
    ['d', 86400],
    ['h', 3600],
    ['m', 60],
];

function format(msRemaining) {
    if (msRemaining <= 0) return 'Closed';

    let seconds = Math.floor(msRemaining / 1000);
    const parts = [];

    for (const [suffix, size] of UNITS) {
        const value = Math.floor(seconds / size);
        if (value > 0 || parts.length > 0) {
            parts.push(`${value}${suffix}`);
            seconds -= value * size;
        }
        if (parts.length === 2) break;
    }

    if (parts.length < 2) {
        parts.push(`${seconds}s`);
    }

    return parts.join(' ');
}

function tick() {
    const now = Date.now();

    $$('[data-countdown] time[datetime]').forEach((el) => {
        const endsAt = Date.parse(el.getAttribute('datetime'));
        if (Number.isNaN(endsAt)) return;

        const remaining = endsAt - now;
        el.textContent = format(remaining);

        const lot = el.closest('[data-lot]');
        if (lot) {
            lot.classList.toggle('is-closed', remaining <= 0);
            // Final hour gets the urgent treatment.
            lot.classList.toggle('is-soon', remaining > 0 && remaining <= 3600_000);
        }
    });
}

export function initCountdowns() {
    if ($$('[data-countdown]').length === 0) return;

    tick();
    setInterval(tick, 1000);
}
