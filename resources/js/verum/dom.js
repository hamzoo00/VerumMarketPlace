/** Tiny DOM helpers shared by the Verum front-end modules. */

export const $ = (selector, root = document) => root.querySelector(selector);

export const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

export const on = (target, type, handler, options) => {
    target?.addEventListener(type, handler, options);
    return () => target?.removeEventListener(type, handler, options);
};

/** Event delegation: handle `type` on any descendant matching `selector`. */
export const delegate = (root, type, selector, handler) =>
    on(root, type, (event) => {
        const match = event.target.closest(selector);
        if (match && root.contains(match)) {
            handler(event, match);
        }
    });

export const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
