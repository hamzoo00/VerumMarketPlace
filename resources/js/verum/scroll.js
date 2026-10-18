import { $, $$, delegate, on, onceGlobal, prefersReducedMotion } from './dom.js';

/** Smooth in-page jumps and the back-to-top progress control. */

function initAnchorScroll() {
    onceGlobal('scroll-anchor', () => delegate(document, 'click', '[data-scrollto]', (event, trigger) => {
        const target = $(trigger.dataset.scrollto);
        if (!target) return;

        event.preventDefault();
        target.scrollIntoView({
            behavior: prefersReducedMotion() ? 'auto' : 'smooth',
            block: 'start',
        });
    }));
}

function initBackToTop() {
    const button = $('#totop');
    if (!button) return;

    const fill = button.querySelector('.totop__fill');
    const circumference = 2 * Math.PI * 22;

    if (fill) {
        fill.style.strokeDasharray = String(circumference);
    }

    const update = () => {
        const max = document.documentElement.scrollHeight - window.innerHeight;
        const progress = max > 0 ? window.scrollY / max : 0;

        button.classList.toggle('is-on', window.scrollY > 600);

        if (fill) {
            fill.style.strokeDashoffset = String(circumference * (1 - progress));
        }
    };

    onceGlobal('scroll-totop', () => on(window, 'scroll', update, { passive: true }));
    update();

    on(button, 'click', () => {
        window.scrollTo({ top: 0, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
    });
}

/** Highlights the section rail as sections pass the viewport midpoint. */
function initSectionRail() {
    const rail = $('#srail');
    const sections = $$('[data-spy]');
    if (!rail || sections.length === 0) return;

    rail.innerHTML = '';

    const links = sections.map((section, index) => {
        const link = document.createElement('a');
        link.href = `#${section.id || `sec-${index}`}`;
        if (!section.id) {
            section.id = `sec-${index}`;
        }
        link.innerHTML = `<i></i><span>${section.dataset.spy}</span>`;
        rail.appendChild(link);
        return link;
    });

    if (!('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const index = sections.indexOf(entry.target);
                links.forEach((link, i) => link.classList.toggle('is-on', i === index));
            });
        },
        { rootMargin: '-45% 0px -45% 0px' },
    );

    sections.forEach((section) => observer.observe(section));
}

export function initScroll() {
    initAnchorScroll();
    initBackToTop();
    initSectionRail();
}
