import { $, onceGlobal } from './dom.js';

/**
 * A conversation, live.
 *
 * The channel is **private** to the thread — Echo authorises against the
 * signed-in guard, so neither party can listen to a conversation they
 * are not in by guessing an id.
 *
 * As elsewhere, the page refetches rather than patching: who sent what,
 * whether it is flagged and how the timestamp reads are all decided in
 * Blade, and duplicating that here would let the two drift.
 */

const SETTLE_MS = 250;

async function refresh() {
    const response = await fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

    if (!response.ok) return;

    const fresh = new DOMParser().parseFromString(await response.text(), 'text/html');

    const log = $('[data-chat-log]');
    const next = fresh.querySelector('[data-chat-log]');

    if (!log || !next) return;

    // Only scroll the reader down if they were already at the bottom —
    // yanking the view while someone reads back is worse than a missed
    // message.
    const wasAtBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 80;

    log.replaceWith(next);

    if (wasAtBottom) {
        next.scrollTop = next.scrollHeight;
    }
}

async function connect() {
    if (window.Echo) return window.Echo;

    await import('../echo.js');

    return window.Echo ?? null;
}

export function initChatLive() {
    const host = $('[data-chat]');
    if (!host) return;

    const id = host.dataset.chat;
    if (!id) return;

    // Open on the newest message, as a chat should.
    const log = $('[data-chat-log]');
    if (log) log.scrollTop = log.scrollHeight;

    onceGlobal('live-chat', async () => {
        const echo = await connect();
        if (!echo) return;

        let queued = null;

        const settle = () => {
            clearTimeout(queued);
            queued = setTimeout(refresh, SETTLE_MS);
        };

        echo.private(`conversation.${id}`)
            .listen('.message.sent', settle)
            // The other side opened the thread, so the receipt under our
            // own last message changes. Same refetch: Blade owns how a
            // receipt reads.
            .listen('.messages.read', settle);
    });
}
