<?php

namespace App\Support;

/**
 * The carriers a parcel can go with, and where to follow one.
 *
 * Read from config/verum_shipping.php so the seller's dropdown, the
 * buyer's "Track parcel" link and what PayPal is told cannot disagree
 * about a carrier's name.
 */
final class Carriers
{
    /** @return array<string, string> key => name, for a select */
    public static function options(): array
    {
        return collect(self::all())->map(fn (array $carrier): string => $carrier['name'])->all();
    }

    public static function has(?string $key): bool
    {
        return $key !== null && isset(self::all()[$key]);
    }

    /**
     * How to show what was stored. Orders from before the dropdown hold
     * free text ("DHL"), which is shown as written.
     */
    public static function name(?string $key): ?string
    {
        return self::all()[$key ?? '']['name'] ?? $key;
    }

    /**
     * The carrier's own tracking page for this number.
     *
     * The number is URL-encoded before it is placed, so a crafted one
     * cannot steer the link anywhere else. An unknown carrier goes to
     * 17TRACK, which recognises the carrier from the number.
     */
    public static function trackingUrl(?string $key, ?string $number): ?string
    {
        if ($number === null || trim($number) === '') {
            return null;
        }

        $template = self::all()[$key ?? '']['url'] ?? self::all()['other']['url'];

        return str_replace(':number', rawurlencode(trim($number)), $template);
    }

    /** PayPal's own name for the carrier; OTHER when it has none. */
    public static function paypalCode(?string $key): string
    {
        return self::all()[$key ?? '']['paypal'] ?? 'OTHER';
    }

    /** The HTML `pattern` and the server regex, from one string. */
    public static function trackingPattern(): string
    {
        return (string) config('verum_shipping.tracking_pattern');
    }

    /** @return array<string, array{name: string, paypal: string, url: string}> */
    private static function all(): array
    {
        /** @var array<string, array{name: string, paypal: string, url: string}> */
        return config('verum_shipping.carriers', []);
    }
}
