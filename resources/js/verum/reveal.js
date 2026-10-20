import { $$, prefersReducedMotion } from './dom.js';

/**
 * Scroll reveals.
 *
 * Content is visible by default in CSS; this only adds the animation class, so
 * nothing is ever hidden from crawlers or from visitors without JavaScript.
 */

export function initReveal(root = document) {
    // Anything added after boot — a Livewire re-render, a live card — was
    // never observed, and [data-reveal] starts it faded and shifted down.
    // Left unobserved it stays there, which reads as a dislocated card.
    const items = $$('[data-reveal]', root);
    if (items.length === 0) return;

    if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-in'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.08 },
    );

    items.forEach((el) => observer.observe(el));
}
