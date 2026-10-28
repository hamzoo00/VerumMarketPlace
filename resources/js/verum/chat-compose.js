import { $, $$, on } from './dom.js';

/**
 * The composer: a live moderation hint and the attachment list.
 *
 * The patterns here mirror config/verum_chat.php. They are a courtesy —
 * the server inspects every message independently and is the only thing
 * that decides. Showing the reminder as someone types means they can
 * reword before sending rather than discover the flag afterwards.
 */

/*
 * References blanked before scanning, exactly as MessageModeration does
 * server-side: a certificate (VR-2026-002448) and a lab number
 * (GIA 2185647321) are both long digit runs, and neither is a number to
 * ring. Without this the phone hint would fire on half the listings
 * here.
 */
const REFERENCES = [
    /\b[A-Za-z]{2,6}(?:-\d[\d-]*|\d[\d-]*)\b/gi,
    /\b(?:GIA|IGI|AGL|GRS|SSEF|Gubelin|GUB|GIT|AIGS|Lotus|ICA|GII|HRD|CGL|EGL)\b(?:\s+(?:report|cert(?:ificate)?|no\.?|number|#))?\s*#?\s*\d[\d\s-]*/gi,
];

const PATTERNS = [
    [/[\w.+-]+\s*(?:@|\(at\)|\[at\]|\s+at\s+)\s*[\w-]+\s*(?:\.|\(dot\)|\[dot\]|\s+dot\s+)\s*[a-z]{2,}/i, 'an email address'],
    // Three shapes: a dialling code, grouped digits, or one unbroken
    // run — the last is the common case and the one that was missing.
    [/(?:\+\d[\d\s().-]{7,}\d)|(?<!\w)\d{2,}[\s().-]+\d{3,}[\s().-]*\d{2,}(?!\w)|(?<![\w.-])\d{9,15}(?![\w.-])/, 'a phone number'],
    [/\b(?:whats\s*app|whatsap+|wsap|wechat|we\s*chat|telegram|viber|kakao|imo|botim|zangi|skype|discord|messenger|signal\s*(?:app|id|number)|(?:on|via|use)\s+signal\b|line\s*(?:id|app)|(?:wa|tg)\s*(?:no|number|id)\b)/i, 'a messaging app'],
    [/\b(?:instagram|insta\b|ig\s*(?:handle|id|page)|facebook|fb\s*(?:page|id)|twitter|tiktok|snapchat|linkedin|pinterest|youtube|(?:alibaba|aliexpress|ebay|etsy|amazon)\s*(?:store|shop|page|listing|profile|link))\b/i, 'a social profile'],
    [/\b(?:https?:\/\/|www\.)\S+/i, 'a link to another site'],
    [/\b(?:paypal|venmo|cash\s*app|zelle|revolut|wise|payoneer|western\s*union|moneygram|ria\s*money|remitly|bank\s*(?:transfer|details|account)|wire\s*(?:transfer|me|payment)|crypto|bitcoin|btc|ethereum|usdt|tether|binance|iban|swift\s*code|routing\s*number|sort\s*code|hawala|easypaisa|jazzcash)\b/i, 'paying outside the platform'],
    [/\b(?:(?:avoid|skip|save|dodge|without|no)\s+(?:the\s+)?(?:fee|fees|commission)|cheaper\s+(?:if|when|direct)|cut\s+out\s+the\s+middle\s*man)\b/i, 'avoiding platform fees'],
    [/\b(?:cancel\s+(?:the\s+)?(?:order|sale|bid|auction)\s+(?:and|then|so)|(?:buy|sell|pay|settle|handle)\s+(?:it\s+)?(?:privately|direct(?:ly)?|between\s+us)|keep\s+(?:this|it)\s+between\s+us)\b/i, 'dealing privately'],
];

function inspect(text) {
    const scannable = REFERENCES.reduce((out, reference) => out.replace(reference, ' '), text);

    return PATTERNS.filter(([pattern]) => pattern.test(scannable)).map(([, label]) => label);
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

    label.textContent = names.join(', ');
    label.hidden = names.length === 0;
}

/**
 * Grow the box with the message.
 *
 * One line to start, up to the cap set in CSS — a fixed three-line box
 * is mostly empty for "yes, still available".
 */
function autoGrow(body) {
    body.style.height = 'auto';
    body.style.height = `${body.scrollHeight}px`;
}

/** Enter sends; Shift+Enter is a new line, as every chat behaves. */
function sendOnEnter(form, body) {
    on(body, 'keydown', (event) => {
        if (event.key !== 'Enter' || event.shiftKey || event.isComposing) return;

        event.preventDefault();

        if (body.value.trim() !== '') form.requestSubmit();
    });
}

/**
 * Filter the rendered thread rows.
 *
 * A convenience over what is already on the page: the server still
 * owns ordering and paging, so this never changes what was fetched.
 */
function initThreadFilter() {
    const box = $('[data-thread-filter]');
    const list = $('[data-thread-list]');
    if (!box || !list || box.dataset.filterReady === 'true') return;

    box.dataset.filterReady = 'true';

    const rows = [...list.querySelectorAll('[data-thread-row]')];
    const none = $('[data-thread-none]');

    on(box, 'input', () => {
        const term = box.value.trim().toLowerCase();
        let shown = 0;

        rows.forEach((row) => {
            const hit = term === '' || (row.dataset.label ?? '').includes(term);
            row.hidden = !hit;
            if (hit) shown += 1;
        });

        if (none) none.hidden = shown > 0;
    });
}

export function initChatComposer() {
    initThreadFilter();

    const form = $('[data-chat-form]');
    if (!form || form.dataset.chatReady === 'true') return;

    form.dataset.chatReady = 'true';

    const body = $('[data-chat-body]', form);
    const warning = $('[data-chat-warning]', form);

    if (body) {
        autoGrow(body);
        on(body, 'input', () => autoGrow(body));
        sendOnEnter(form, body);
    }

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
