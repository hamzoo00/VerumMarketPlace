<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Throwable;

/**
 * A QR code as inline SVG.
 *
 * Rendered server-side rather than drawn in JS, like every other piece
 * of markup here: a crawler sees it, it survives with scripting off, and
 * it needs no extra request.
 *
 * bacon/bacon-qr-code is already present as a Fortify dependency, so
 * this adds nothing to the lock file.
 */
final class QrCode
{
    /**
     * @param  int  $size  Pixel size of the square.
     * @return string|null SVG markup, or null if it could not be drawn.
     */
    public static function svg(string $text, int $size = 120): ?string
    {
        if (trim($text) === '') {
            return null;
        }

        try {
            $writer = new Writer(new ImageRenderer(
                // No quiet zone: the card already provides the margin,
                // and the default border makes the code look adrift.
                new RendererStyle($size, 0),
                new SvgImageBackEnd,
            ));

            $svg = $writer->writeString($text);

            // The writer emits a full document; the card needs the
            // element alone so it can be styled in place.
            return preg_replace('/<\?xml.*?\?>\s*/s', '', $svg) ?: null;
        } catch (Throwable) {
            // A missing code is a cosmetic loss; the reference below it
            // is what actually verifies the stone.
            return null;
        }
    }
}
