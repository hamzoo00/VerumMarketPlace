import { $$, on, onceGlobal, prefersReducedMotion } from './dom.js';

/**
 * Listing imagery.
 *
 * Two shapes share one idea — a strip of server-rendered <img> where exactly
 * one carries `.is-on`. Nothing is created in JS, so a crawler still sees
 * every photograph.
 *
 * - Cards cross-fade on their own, and pause while hovered so a shopper can
 *   look at what caught their eye.
 * - The detail gallery adds arrows, thumbnails and a counter, and stops
 *   rotating for good once the visitor takes control.
 */

const CARD_INTERVAL = 3200;
const STAGE_INTERVAL = 5000;

function show(slides, index) {
    slides.forEach((slide, i) => slide.classList.toggle('is-on', i === index));
}

/* ---------- cards ---------- */

function initCardShots(root) {
    if (root.dataset.shotsReady === 'true') return;
    root.dataset.shotsReady = 'true';

    const slides = $$('.shots__img', root);
    const dots = $$('.shots__dots i', root);
    if (slides.length < 2) return;

    let index = 0;
    let timer = null;

    const step = () => {
        index = (index + 1) % slides.length;
        show(slides, index);
        show(dots, index);
    };

    const start = () => {
        if (timer === null && !prefersReducedMotion()) {
            timer = setInterval(step, CARD_INTERVAL);
        }
    };

    const stop = () => {
        clearInterval(timer);
        timer = null;
    };

    // Only animate what is on screen: a shop page holds a dozen of these.
    const io = new IntersectionObserver(
        (entries) => entries.forEach((entry) => (entry.isIntersecting ? start() : stop())),
        { rootMargin: '100px' },
    );
    io.observe(root);

    const card = root.closest('.scard') ?? root;
    on(card, 'pointerenter', stop);
    on(card, 'pointerleave', start);
}

/* ---------- detail gallery ---------- */

function initStage(root) {
    if (root.dataset.galleryReady === 'true') return;
    root.dataset.galleryReady = 'true';

    const slides = $$('.gallery__img', root);
    const thumbs = $$('[data-gallery-go]', root);
    const counter = root.querySelector('[data-gallery-count]');
    if (slides.length < 2) return;

    let index = 0;
    let timer = null;

    const go = (next) => {
        index = (next + slides.length) % slides.length;
        show(slides, index);

        thumbs.forEach((thumb, i) => {
            thumb.classList.toggle('is-on', i === index);
            thumb.setAttribute('aria-selected', i === index ? 'true' : 'false');
        });

        if (counter) counter.textContent = `${index + 1} / ${slides.length}`;
    };

    const stop = () => {
        clearInterval(timer);
        timer = null;
    };

    const start = () => {
        if (timer === null && !prefersReducedMotion()) {
            timer = setInterval(() => go(index + 1), STAGE_INTERVAL);
        }
    };

    /** A deliberate choice ends the slideshow: it must not move under them. */
    const take = (next) => {
        stop();
        root.dataset.galleryHeld = 'true';
        go(next);
    };

    on(root.querySelector('[data-gallery-prev]'), 'click', () => take(index - 1));
    on(root.querySelector('[data-gallery-next]'), 'click', () => take(index + 1));
    thumbs.forEach((thumb, i) => on(thumb, 'click', () => take(i)));

    on(root, 'keydown', (event) => {
        if (event.key === 'ArrowLeft') take(index - 1);
        if (event.key === 'ArrowRight') take(index + 1);
    });

    on(root, 'pointerenter', stop);
    on(root, 'pointerleave', () => {
        if (root.dataset.galleryHeld !== 'true') start();
    });

    start();
}

export function initGalleries() {
    $$('[data-shots]').forEach(initCardShots);
    $$('[data-gallery]').forEach(initStage);

    // Arrow keys reach the gallery even when focus sits on a child button.
    onceGlobal('gallery-focus', () => {
        document.addEventListener('focusin', (event) => {
            event.target.closest('[data-gallery]')?.setAttribute('tabindex', '-1');
        });
    });

    // A card pushed in over the socket was never in the DOM at boot.
    onceGlobal('gallery-live', () => {
        document.addEventListener('verum:content-added', (event) => {
            const root = event.detail?.root;
            if (!root) return;

            $$('[data-shots]', root).forEach(initCardShots);
            if (root.matches?.('[data-shots]')) initCardShots(root);
        });
    });
}
