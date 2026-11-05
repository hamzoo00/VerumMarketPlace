<?php

namespace App\Support;

use Illuminate\Support\Str;

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

    /** The tracking API's code for the carrier; null lets it detect one from the number. */
    public static function trackerCode(?string $key): ?int
    {
        $code = self::all()[$key ?? '']['17track'] ?? null;

        return $code === null ? null : (int) $code;
    }

    /**
     * The number as carriers match it: no spaces, upper case.
     *
     * Receipts print "7712 3456 7890"; a courier API and a webhook both
     * key on "771234567890". Stored this way, a report finds its order.
     */
    public static function normaliseNumber(string $number): string
    {
        return Str::upper((string) preg_replace('/\s+/', '', $number));
    }

    /** The HTML `pattern` and the server regex, from one string. */
    public static function trackingPattern(): string
    {
        return (string) config('verum_shipping.tracking_pattern');
    }

    /** @return array<string, array{name: string, paypal: string, '17track': int|null, url: string}> */
    private static function all(): array
    {
        /** @var array<string, array{name: string, paypal: string, '17track': int|null, url: string}> */
        return config('verum_shipping.carriers', []);
    }
}
