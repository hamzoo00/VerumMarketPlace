import { $, on } from './dom.js';

/**
 * A small crop-and-position step before an image is uploaded.
 *
 * The seller pans and zooms; the canvas renders exactly what the frame
 * shows and hands back a cropped file. Doing it here rather than on the
 * server means the upload is already the right shape and size — a 12 MB
 * phone photo becomes a few hundred KB before it leaves the browser.
 *
 * The server still validates everything it receives. This is convenience,
 * never a control.
 */

const OUTPUT = {
    avatar: { width: 640, height: 640 },
    banner: { width: 1920, height: 600 },
};

const MIN_ZOOM = 1;
const MAX_ZOOM = 4;

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

/**
 * Keeps the image covering the frame.
 *
 * Panning past an edge would letterbox the result, so the offsets are
 * bounded by however much image lies outside the frame.
 */
function clampOffsets(state) {
    const { frameW, frameH, naturalW, naturalH, zoom } = state;

    const scale = Math.max(frameW / naturalW, frameH / naturalH) * zoom;
    const drawnW = naturalW * scale;
    const drawnH = naturalH * scale;

    const slackX = Math.max(0, (drawnW - frameW) / 2);
    const slackY = Math.max(0, (drawnH - frameH) / 2);

    state.offsetX = clamp(state.offsetX, -slackX, slackX);
    state.offsetY = clamp(state.offsetY, -slackY, slackY);
    state.scale = scale;
}

function paint(canvas, image, state) {
    clampOffsets(state);

    const context = canvas.getContext('2d');
    const { frameW, frameH, naturalW, naturalH, scale, offsetX, offsetY } = state;

    context.clearRect(0, 0, frameW, frameH);
    context.drawImage(
        image,
        (frameW - naturalW * scale) / 2 + offsetX,
        (frameH - naturalH * scale) / 2 + offsetY,
        naturalW * scale,
        naturalH * scale,
    );
}

/** Re-render at full output size, so the saved file is not preview-sized. */
function render(image, state, kind) {
    const out = OUTPUT[kind] ?? OUTPUT.avatar;
    const ratio = out.width / state.frameW;

    const canvas = document.createElement('canvas');
    canvas.width = out.width;
    canvas.height = out.height;

    const scale = state.scale * ratio;
    const context = canvas.getContext('2d');

    context.drawImage(
        image,
        (out.width - state.naturalW * scale) / 2 + state.offsetX * ratio,
        (out.height - state.naturalH * scale) / 2 + state.offsetY * ratio,
        state.naturalW * scale,
        state.naturalH * scale,
    );

    return canvas;
}

/**
 * Opens the editor for one file.
 *
 * @returns {Promise<File|null>} the cropped file, or null if cancelled.
 */
export function cropImage(file, kind) {
    return new Promise((resolve) => {
        const modal = $('[data-cropper]');
        const canvas = $('[data-cropper-canvas]', modal);
        const zoomInput = $('[data-cropper-zoom]', modal);

        if (!modal || !canvas) {
            resolve(file);

            return;
        }

        const objectUrl = URL.createObjectURL(file);
        const image = new Image();

        const state = {
            frameW: OUTPUT[kind]?.width ?? 640,
            frameH: OUTPUT[kind]?.height ?? 640,
            naturalW: 0,
            naturalH: 0,
            zoom: 1,
            offsetX: 0,
            offsetY: 0,
            scale: 1,
        };

        // A preview no wider than the panel, keeping the output's shape.
        const previewW = kind === 'banner' ? 520 : 320;
        state.frameW = previewW;
        state.frameH = Math.round(previewW * ((OUTPUT[kind]?.height ?? 640) / (OUTPUT[kind]?.width ?? 640)));

        canvas.width = state.frameW;
        canvas.height = state.frameH;

        const close = (result) => {
            modal.hidden = true;
            document.body.classList.remove('is-locked');
            URL.revokeObjectURL(objectUrl);
            teardown.forEach((off) => off());
            resolve(result);
        };

        const teardown = [];

        image.addEventListener('load', () => {
            state.naturalW = image.naturalWidth;
            state.naturalH = image.naturalHeight;

            if (zoomInput) {
                zoomInput.min = String(MIN_ZOOM);
                zoomInput.max = String(MAX_ZOOM);
                zoomInput.step = '0.01';
                zoomInput.value = '1';
            }

            modal.hidden = false;
            document.body.classList.add('is-locked');
            paint(canvas, image, state);
        });

        image.src = objectUrl;

        // --- panning -------------------------------------------------
        let dragging = false;
        let lastX = 0;
        let lastY = 0;

        teardown.push(on(canvas, 'pointerdown', (event) => {
            dragging = true;
            lastX = event.clientX;
            lastY = event.clientY;
            canvas.setPointerCapture(event.pointerId);
        }));

        teardown.push(on(canvas, 'pointermove', (event) => {
            if (!dragging) return;

            state.offsetX += event.clientX - lastX;
            state.offsetY += event.clientY - lastY;
            lastX = event.clientX;
            lastY = event.clientY;
            paint(canvas, image, state);
        }));

        teardown.push(on(canvas, 'pointerup', () => { dragging = false; }));
        teardown.push(on(canvas, 'pointercancel', () => { dragging = false; }));

        // --- zoom ----------------------------------------------------
        teardown.push(on(zoomInput, 'input', () => {
            state.zoom = clamp(Number(zoomInput.value), MIN_ZOOM, MAX_ZOOM);
            paint(canvas, image, state);
        }));

        teardown.push(on(canvas, 'wheel', (event) => {
            event.preventDefault();
            state.zoom = clamp(state.zoom - event.deltaY * 0.002, MIN_ZOOM, MAX_ZOOM);

            if (zoomInput) zoomInput.value = String(state.zoom);

            paint(canvas, image, state);
        }, { passive: false }));

        // --- actions -------------------------------------------------
        teardown.push(on($('[data-cropper-save]', modal), 'click', () => {
            render(image, state, kind).toBlob(
                (blob) => {
                    if (!blob) {
                        close(null);

                        return;
                    }

                    const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                    close(new File([blob], name, { type: 'image/jpeg' }));
                },
                'image/jpeg',
                0.9,
            );
        }));

        teardown.push(on($('[data-cropper-cancel]', modal), 'click', () => close(null)));
        teardown.push(on($('[data-cropper-scrim]', modal), 'click', () => close(null)));

        teardown.push(on(document, 'keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) close(null);
        }));
    });
}
