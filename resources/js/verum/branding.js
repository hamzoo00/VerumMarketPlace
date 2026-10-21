import { cropImage } from './cropper.js';
import { $, $$, on } from './dom.js';

/**
 * A seller's avatar and store banner.
 *
 * One image each, replaced rather than stacked. Validation happens here
 * *and* on the server: the client check only spares the seller a pointless
 * upload, it is never what makes the limit real.
 *
 * XMLHttpRequest, not fetch, because fetch cannot report upload progress.
 */

function token() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function problem(file, widget) {
    const maxKb = Number(widget.dataset.maxKb);
    const accept = widget.dataset.accept.split(',');

    if (!accept.includes(file.type)) {
        return 'That format is not accepted.';
    }

    if (file.size / 1024 > maxKb) {
        return `That image is too large. The limit is ${Math.round(maxKb / 1024)} MB.`;
    }

    return null;
}

function showPreview(widget, url) {
    const preview = $('[data-image-preview]', widget);
    if (!preview) return;

    preview.innerHTML = '';

    const img = document.createElement('img');
    img.src = url;
    img.alt = '';
    preview.appendChild(img);

    const remove = $('[data-image-remove]', widget);
    if (remove) remove.hidden = false;
}

function setProgress(widget, percent) {
    const bar = $('[data-image-bar]', widget);
    if (!bar) return;

    bar.hidden = percent === null;

    if (percent !== null) {
        bar.firstElementChild.style.width = `${percent}%`;
    }
}

function upload(widget, file) {
    const complaint = problem(file, widget);

    if (complaint) {
        window.verumToast?.(complaint, 'error');

        return;
    }

    const body = new FormData();
    body.append('file', file);
    body.append('kind', widget.dataset.imageUpload);

    const request = new XMLHttpRequest();
    request.open('POST', widget.dataset.url);
    request.setRequestHeader('X-CSRF-TOKEN', token());
    request.setRequestHeader('Accept', 'application/json');

    request.upload.addEventListener('progress', (event) => {
        if (event.lengthComputable) {
            setProgress(widget, Math.round((event.loaded / event.total) * 100));
        }
    });

    request.addEventListener('load', () => {
        setProgress(widget, null);

        let payload = {};
        try {
            payload = JSON.parse(request.responseText);
        } catch {
            /* a non-JSON body means something upstream failed */
        }

        if (request.status === 201 && payload.data?.url) {
            showPreview(widget, payload.data.url);
            window.verumToast?.('Image updated.', 'success');

            return;
        }

        const message = payload.errors?.file?.[0]
            ?? payload.message
            ?? 'That upload failed.';

        window.verumToast?.(message, 'error');
    });

    request.addEventListener('error', () => {
        setProgress(widget, null);
        window.verumToast?.('That upload failed.', 'error');
    });

    setProgress(widget, 0);
    request.send(body);
}

async function remove(widget) {
    const response = await fetch(widget.dataset.url, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': token(),
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify({ kind: widget.dataset.imageUpload }),
    });

    if (!response.ok) {
        window.verumToast?.('That could not be removed.', 'error');

        return;
    }

    const preview = $('[data-image-preview]', widget);
    if (preview) {
        preview.innerHTML = '<span class="imgup__placeholder" aria-hidden="true">'
            + '<svg class="ic"><use href="#i-upload"/></svg></span>';
    }

    const button = $('[data-image-remove]', widget);
    if (button) button.hidden = true;

    window.verumToast?.('Image removed.', 'success');
}

export function initBranding() {
    $$('[data-image-upload]').forEach((widget) => {
        if (widget.dataset.brandingReady === 'true') return;
        widget.dataset.brandingReady = 'true';

        const input = $('[data-image-input]', widget);

        on(input, 'change', async () => {
            const file = input.files?.[0];

            // Clear it now, so choosing the same file twice still fires
            // even while the editor is open.
            input.value = '';

            if (!file) return;

            // Reject before the editor: no point cropping a file that
            // cannot be uploaded anyway.
            const complaint = problem(file, widget);

            if (complaint) {
                window.verumToast?.(complaint, 'error');

                return;
            }

            const cropped = await cropImage(file, widget.dataset.imageUpload);

            // null means the seller cancelled.
            if (cropped) upload(widget, cropped);
        });

        on($('[data-image-remove]', widget), 'click', () => remove(widget));
    });
}
