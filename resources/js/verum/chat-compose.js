import { $, $$, on } from './dom.js';

/**
 * The composer: a live moderation hint and the attachment list.
 *
 * The patterns here mirror config/verum_chat.php. They are a courtesy —
 * the server inspects every message independently and is the only thing
 * that decides. Showing the reminder as someone types means they can
 * reword before sending rather than discover the flag afterwards.
 */

const PATTERNS = [
    [/[\w.+-]+\s*(?:@|\(at\)|\[at\]|\s+at\s+)\s*[\w-]+\s*(?:\.|\(dot\)|\[dot\]|\s+dot\s+)\s*[a-z]{2,}/i, 'an email address'],
    [/(?:\+\d[\d\s().-]{8,}\d)|(?:(?<![\w-])\d{3,}[\s()]+\d{3,}[\s()]*\d{2,}(?![\w-]))/, 'a phone number'],
    [/\b(?:whats\s*app|wechat|telegram|signal|viber|kakao|imo)\b/i, 'a messaging app'],
    [/\b(?:instagram|facebook|twitter|tiktok|snapchat|linkedin)\b/i, 'a social profile'],
    [/\b(?:https?:\/\/|www\.)\S+/i, 'a link to another site'],
    [/\b(?:paypal|western\s*union|moneygram|bank\s*transfer|wire\s*transfer|crypto|bitcoin|usdt|iban)\b/i, 'paying outside the platform'],
];

function inspect(text) {
    return PATTERNS.filter(([pattern]) => pattern.test(text)).map(([, label]) => label);
}

function syncWarning(body, warning) {
    const found = inspect(body.value);

    if (found.length === 0) {
        warning.hidden = true;

        return;
    }

    const list = found.length === 1
        ? found[0]
        : `${found.slice(0, -1).join(', ')} and ${found.at(-1)}`;

    // textContent, never innerHTML: this echoes what the sender typed.
    warning.textContent =
        `This looks like it contains ${list}. Keeping the conversation here means you are covered if something goes wrong.`;
    warning.hidden = false;
}

function syncFiles(input, label) {
    const names = [...(input.files ?? [])].map((file) => file.name);

    if (names.length > 0) {
        label.textContent = names.join(', ');
    }
}

export function initChatComposer() {
    const form = $('[data-chat-form]');
    if (!form || form.dataset.chatReady === 'true') return;

    form.dataset.chatReady = 'true';

    const body = $('[data-chat-body]', form);
    const warning = $('[data-chat-warning]', form);

    if (body && warning) {
        on(body, 'input', () => syncWarning(body, warning));
        syncWarning(body, warning);
    }

    $$('[data-chat-files]', form).forEach((input) => {
        const label = $('[data-chat-filenames]', form);

        if (label) {
            on(input, 'change', () => syncFiles(input, label));
        }
    });
}
