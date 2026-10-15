import { $$, on } from './dom.js';

const desktopCategories = window.matchMedia('(min-width: 901px)');

/** Expand category panels before following their shop links. */
export function initCategories() {
    $$('.cats').forEach((root) => {
        if (root.dataset.categoriesReady === 'true') return;

        root.dataset.categoriesReady = 'true';

        const categories = $$('[data-cat]', root);
        const openCategory = (category) => {
            categories.forEach((candidate) => {
                candidate.classList.toggle('is-open', candidate === category);
            });
        };

        categories.forEach((category) => {
            on(category, 'mouseenter', () => openCategory(category));
            on(category, 'focus', () => openCategory(category));
            on(category, 'click', (event) => {
                if (desktopCategories.matches && !category.classList.contains('is-open')) {
                    event.preventDefault();
                    openCategory(category);
                }
            });
        });
    });
}
