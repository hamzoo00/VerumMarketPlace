import { $, on, onceGlobal } from './dom.js';

/**
 * Global messages.
 *
 * One host in the layout; anything can raise a message with
 * `window.verumToast('Saved', 'success')`. Server-flashed messages are
 * handed over in a data attribute so there is one rendering path.
 */

const ICONS = {
    success: 'check',
    error: 'close',
    warning: 'info',
    info: 'gem',
};

const DURATIONS = {
    success: 4000,
    info: 4000,
    warning: 6000,
    // Errors stay until dismissed: they usually need an action.
    error: 0,
};

function host() {
    return $('[data-toast-host]');
}

export function toast(message, status = 'info') {
    const root = host();
    if (!root || !message) return;

    const kind = Object.hasOwn(ICONS, status) ? status : 'info';

    const el = document.createElement('div');
    el.className = `toast toast--${kind}`;
    el.setAttribute('role', kind === 'error' ? 'alert' : 'status');

    // The gem is a bezel, not decoration: it carries the status colour.
    el.innerHTML = `
        <span class="toast__gem" aria-hidden="true">
            <svg class="ic"><use href="#i-${ICONS[kind]}"/></svg>
        </span>
        <p></p>
        <button class="toast__x" type="button" aria-label="Dismiss">
            <svg class="ic" aria-hidden="true"><use href="#i-close"/></svg>
        </button>
        <span class="toast__thread" aria-hidden="true"></span>`;

    // textContent, never innerHTML: the message may contain user input.
    el.querySelector('p').textContent = message;

    const remove = () => {
        el.classList.remove('is-in');
        el.addEventListener('transitionend', () => el.remove(), { once: true });
        setTimeout(() => el.remove(), 400);
    };

    on(el.querySelector('.toast__x'), 'click', remove);

    root.appendChild(el);
    requestAnimationFrame(() => el.classList.add('is-in'));

    const life = DURATIONS[kind];
    if (life > 0) {
        // The thread drains for exactly as long as the toast lives, so the
        // countdown is visible rather than a surprise.
        el.style.setProperty('--life', `${life}ms`);
        el.classList.add('is-timed');
        setTimeout(remove, life);
    }

    return remove;
}

export function initToasts() {
    const root = host();
    if (!root) return;

    // Expose once, so any module or inline script can raise a message.
    onceGlobal('toast-global', () => {
        window.verumToast = toast;
    });

    const flash = root.dataset.flash;
    if (!flash) return;

    // Consume it, so a Livewire navigation does not replay old messages.
    delete root.dataset.flash;
    root.removeAttribute('data-flash');

    try {
        JSON.parse(flash).forEach(({ status, message }) => toast(message, status));
    } catch {
        /* malformed payload: nothing to show */
    }
}
