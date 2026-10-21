{{--
    One editor, shared by every image upload on the page.
    Rendered once in the layout that needs it; cropper.js drives it.
--}}
<div class="cropper" data-cropper role="dialog" aria-modal="true" aria-labelledby="cropTitle" hidden>
    <div class="cropper__scrim" data-cropper-scrim></div>

    <div class="cropper__panel">
        <h2 class="cropper__t" id="cropTitle">{{ __('Position your image') }}</h2>
        <p class="cropper__hint">{{ __('Drag to move, scroll or use the slider to zoom.') }}</p>

        <div class="cropper__stage">
            <canvas data-cropper-canvas></canvas>
        </div>

        <label class="cropper__zoom">
            <span class="sr-only">{{ __('Zoom') }}</span>
            <x-verum.icon name="loupe" />
            <input type="range" data-cropper-zoom value="1" min="1" max="4" step="0.01">
        </label>

        <div class="cropper__btns">
            <button class="btn btn--ghost btn--sm" type="button" data-cropper-cancel>{{ __('Cancel') }}</button>
            <button class="btn btn--ruby btn--sm" type="button" data-cropper-save>{{ __('Use this') }}</button>
        </div>
    </div>
</div>
