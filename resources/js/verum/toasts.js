import { $ } from './dom.js';

/** Transient status messages, announced politely to screen readers. */

const DURATION = 4200;

export function toast(message, { icon = 'check' } = {}) {
    const host = $('#toasts');
    if (!host) return;

    const el = document.createElement('div');
    el.className = 'toast';
    el.innerHTML = `<svg class="ic" aria-hidden="true"><use href="#i-${icon}"/></svg><p></p>`;
    el.querySelector('p').textContent = message;

    host.appendChild(el);
    requestAnimationFrame(() => el.classList.add('is-in'));

    const remove = () => {
        el.classList.remove('is-in');
        el.addEventListener('transitionend', () => el.remove(), { once: true });
    };

    setTimeout(remove, DURATION);
    el.addEventListener('click', remove);
}
