import { $, $$, on, delegate, onceGlobal } from './dom.js';
import { toast } from './toast.js';

/**
 * File uploads with per-file progress.
 *
 * XMLHttpRequest rather than fetch, because only XHR reports upload
 * progress. One request per file: progress is real, and one rejected file
 * does not take the others with it.
 */

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function humanSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

/** Rejects before upload, so an oversized file never leaves the browser. */
function localProblem(file, widget) {
    const maxKb = Number(widget.dataset.maxKb);
    const accept = widget.dataset.accept.split(',');

    if (!accept.includes(file.type)) {
        return `${file.name}: that format is not accepted.`;
    }

    if (file.size / 1024 > maxKb) {
        return `${file.name} is ${humanSize(file.size)}; the limit is ${Math.round(maxKb / 1024)} MB.`;
    }

    return null;
}

function queueRow(file) {
    const row = document.createElement('li');
    row.className = 'uprog';
    row.innerHTML = `
        <div class="uprog__top">
            <span class="uprog__name"></span>
            <span class="uprog__pct">0%</span>
        </div>
        <div class="uprog__bar"><i></i></div>`;
    row.querySelector('.uprog__name').textContent = `${file.name} · ${humanSize(file.size)}`;

    return row;
}

function renderMedia(widget, data) {
    const items = $('[data-upload-items]', widget);
    const isVideo = widget.dataset.kind === 'videos';

    const li = document.createElement('li');
    li.className = 'umedia';
    li.dataset.mediaId = String(data.id);

    if (isVideo) {
        const video = document.createElement('video');
        video.className = 'umedia__v';
        video.src = data.url;
        video.controls = true;
        video.preload = 'metadata';
        li.appendChild(video);
    } else {
        const img = document.createElement('img');
        img.className = 'umedia__img';
        img.src = data.thumb ?? data.url;
        img.alt = data.name;
        li.appendChild(img);
    }

    const remove = document.createElement('button');
    remove.className = 'umedia__x';
    remove.type = 'button';
    remove.dataset.mediaRemove = data.delete_url;
    remove.setAttribute('aria-label', `Remove ${data.name}`);
    remove.innerHTML = '<svg class="ic" aria-hidden="true"><use href="#i-close"/></svg>';
    li.appendChild(remove);

    items.appendChild(li);
    widget.dataset.count = String(Number(widget.dataset.count) + 1);
}

function upload(file, widget) {
    return new Promise((resolve) => {
        const queue = $('[data-upload-queue]', widget);
        const row = queueRow(file);
        const bar = row.querySelector('.uprog__bar i');
        const pct = row.querySelector('.uprog__pct');
        queue.appendChild(row);

        const body = new FormData();
        body.append('file', file);
        body.append('kind', widget.dataset.kind);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', widget.dataset.endpoint);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf());
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) return;

            const percent = Math.round((event.loaded / event.total) * 100);
            bar.style.width = `${percent}%`;
            pct.textContent = `${percent}%`;
        });

        xhr.addEventListener('load', () => {
            row.remove();

            if (xhr.status === 201) {
                renderMedia(widget, JSON.parse(xhr.responseText).data);
                resolve(true);

                return;
            }

            // Laravel returns 422 with the field message; anything else is
            // unexpected and gets a generic line.
            let message = `${file.name} could not be uploaded.`;

            try {
                const payload = JSON.parse(xhr.responseText);
                message = payload.errors?.file?.[0] ?? payload.message ?? message;
            } catch {
                /* keep the generic message */
            }

            toast(message, 'error');
            resolve(false);
        });

        xhr.addEventListener('error', () => {
            row.remove();
            toast(`${file.name} failed to upload. Check your connection.`, 'error');
            resolve(false);
        });

        xhr.send(body);
    });
}

/** Sequential, so progress bars stay meaningful and the server is not flooded. */
async function handleFiles(files, widget) {
    const max = Number(widget.dataset.maxFiles);

    for (const file of files) {
        if (Number(widget.dataset.count) >= max) {
            toast(`You can attach at most ${max} files here.`, 'warning');
            break;
        }

        const problem = localProblem(file, widget);

        if (problem) {
            toast(problem, 'error');
            continue;
        }

        await upload(file, widget);
    }
}

export function initUploads() {
    const widgets = $$('[data-upload]');
    if (widgets.length === 0) return;

    widgets.forEach((widget) => {
        if (widget.dataset.uploadReady === 'true') return;
        widget.dataset.uploadReady = 'true';

        const input = $('[data-upload-input]', widget);
        const drop = $('[data-upload-drop]', widget);

        on(input, 'change', () => {
            handleFiles([...input.files], widget);
            // Reset, so choosing the same file again still fires change.
            input.value = '';
        });

        ['dragenter', 'dragover'].forEach((type) =>
            on(drop, type, (event) => {
                event.preventDefault();
                drop.classList.add('is-over');
            }),
        );

        ['dragleave', 'drop'].forEach((type) =>
            on(drop, type, (event) => {
                event.preventDefault();
                drop.classList.remove('is-over');
            }),
        );

        on(drop, 'drop', (event) => {
            handleFiles([...(event.dataTransfer?.files ?? [])], widget);
        });
    });

    onceGlobal('upload-remove', () => {
        delegate(document, 'click', '[data-media-remove]', async (event, button) => {
            event.preventDefault();

            const item = button.closest('.umedia');
            const widget = button.closest('[data-upload]');
            button.disabled = true;

            try {
                const response = await fetch(button.dataset.mediaRemove, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                });

                if (!response.ok) throw new Error('failed');

                item?.remove();

                if (widget) {
                    widget.dataset.count = String(Math.max(0, Number(widget.dataset.count) - 1));
                }
            } catch {
                button.disabled = false;
                toast('That file could not be removed. Try again.', 'error');
            }
        });
    });
}
