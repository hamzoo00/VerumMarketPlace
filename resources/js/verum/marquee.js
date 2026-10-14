import { $$, prefersReducedMotion } from './dom.js';

/**
 * Continuous marquees (the header ticker and the promise band).
 *
 * The track is duplicated so the loop is seamless, then translated with a
 * single rAF pass shared by every marquee on the page.
 */

const marquees = [];
let frame = null;

function step() {
    marquees.forEach((m) => {
        m.offset -= m.speed;
        if (-m.offset >= m.width) {
            m.offset += m.width;
        }
        m.track.style.transform = `translate3d(${m.offset}px,0,0)`;
    });

    frame = requestAnimationFrame(step);
}

export function initMarquees() {
    const hosts = $$('[data-marquee]');
    if (hosts.length === 0 || prefersReducedMotion()) return;

    hosts.forEach((host) => {
        const track = host.querySelector('.marquee__track');
        if (!track) return;

        // Duplicate the content so there is always material to scroll into.
        const width = track.scrollWidth;
        track.innerHTML += track.innerHTML;

        marquees.push({
            track,
            width,
            offset: 0,
            speed: Number.parseFloat(host.dataset.speed ?? '0.5'),
        });
    });

    if (marquees.length > 0 && frame === null) {
        frame = requestAnimationFrame(step);
    }
}
