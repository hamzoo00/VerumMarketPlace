import { $, $$, on } from './dom.js';

/**
 * Category browser: a column per level, like a file browser.
 *
 * Children load on demand from the seller categories endpoint, so the page
 * never ships the whole tree. Only a leaf can be saved — a branch exists to
 * navigate through.
 */

const LEVEL_LABEL = (depth) => `Level ${depth + 1}`;

function createPicker(root) {
    const input = $('[data-category-input]', root);
    const chosen = $('[data-category-chosen]', root);
    const modal = $('[data-category-modal]', root);
    const columns = $('[data-category-columns]', root);
    const preview = $('[data-category-preview]', root);
    const saveButton = $('[data-category-save]', root);
    const openButton = $('[data-category-open]', root);

    if (!input || !modal || !columns) return;

    // The trail of nodes clicked so far, root first.
    let trail = [];
    let kind = root.dataset.kind;

    const endpointFor = (parentId) => {
        const base = root.dataset.endpoint.replace('__KIND__', kind);

        return parentId ? `${base}?parent=${parentId}` : base;
    };

    async function fetchChildren(parentId) {
        try {
            const response = await fetch(endpointFor(parentId), {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) return [];

            return (await response.json()).data ?? [];
        } catch {
            return [];
        }
    }

    function renderColumn(items, depth) {
        // Drop any columns to the right of the one being replaced.
        $$('[data-level]', columns)
            .filter((column) => Number(column.dataset.level) >= depth)
            .forEach((column) => column.remove());

        if (items.length === 0) return;

        const column = document.createElement('div');
        column.className = 'catcol';
        column.dataset.level = String(depth);
        column.innerHTML = `<p class="catcol__t">${LEVEL_LABEL(depth)}</p>`;

        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'catcol__item';
            button.dataset.id = String(item.id);
            button.dataset.name = item.name;
            button.dataset.hasChildren = String(item.has_children);
            button.innerHTML = item.has_children
                ? `<span>${item.name}</span><svg class="ic"><use href="#i-chev"/></svg>`
                : `<span>${item.name}</span>`;

            on(button, 'click', () => select(item, depth, column));
            column.appendChild(button);
        });

        columns.appendChild(column);
        column.scrollIntoView({ block: 'nearest', inline: 'end', behavior: 'smooth' });
    }

    async function select(item, depth, column) {
        trail = trail.slice(0, depth);
        trail.push(item);

        $$('.catcol__item', column).forEach((el) => {
            el.classList.toggle('is-on', el.dataset.id === String(item.id));
        });

        updatePreview();

        if (item.has_children) {
            renderColumn(await fetchChildren(item.id), depth + 1);
        } else {
            // A leaf ends the trail; nothing deeper to show.
            $$('[data-level]', columns)
                .filter((c) => Number(c.dataset.level) > depth)
                .forEach((c) => c.remove());
        }
    }

    function updatePreview() {
        const path = trail.map((item) => item.name).join(' › ');
        const leaf = trail.at(-1);
        const selectable = Boolean(leaf) && !leaf.has_children;

        if (preview) {
            preview.textContent = path || '';
            preview.classList.toggle('is-invalid', Boolean(leaf) && !selectable);
        }

        if (saveButton) {
            saveButton.disabled = !selectable;
            saveButton.title = selectable ? '' : 'Choose a specific category, not a group.';
        }
    }

    function openModal() {
        modal.hidden = false;
        requestAnimationFrame(() => modal.classList.add('is-open'));
        document.body.style.overflow = 'hidden';

        if ($$('[data-level]', columns).length === 0) {
            fetchChildren(null).then((items) => renderColumn(items, 0));
        }
    }

    function closeModal() {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.hidden = true;
        }, 250);
    }

    function commit() {
        const leaf = trail.at(-1);
        if (!leaf || leaf.has_children) return;

        input.value = String(leaf.id);

        if (chosen) {
            chosen.innerHTML = '';
            const label = document.createElement('span');
            label.textContent = trail.map((item) => item.name).join(' › ');
            chosen.appendChild(label);
        }

        if (openButton) openButton.textContent = 'Change category';

        closeModal();
    }

    on(openButton, 'click', openModal);
    $$('[data-category-close]', root).forEach((el) => on(el, 'click', closeModal));
    on(saveButton, 'click', commit);

    // Switching branch invalidates everything already chosen.
    $$('[data-kind-choice]', root).forEach((button) => {
        on(button, 'click', () => {
            kind = button.dataset.kindChoice;
            root.dataset.kind = kind;
            trail = [];
            columns.innerHTML = '';
            input.value = '';

            $$('[data-kind-choice]', root).forEach((other) => {
                other.classList.toggle('is-on', other === button);
            });

            if (chosen) chosen.innerHTML = '<span class="catpick__none">No category chosen yet</span>';
            updatePreview();
        });
    });

    on(document, 'keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });
}

export function initCategoryPicker() {
    $$('[data-category-picker]').forEach((root) => {
        if (root.dataset.pickerReady === 'true') return;
        root.dataset.pickerReady = 'true';
        createPicker(root);
    });
}
