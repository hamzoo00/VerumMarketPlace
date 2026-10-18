<?php

namespace App\Support;

/**
 * Country list and dialling codes, read from config/countries.php.
 *
 * Keeps the lookup in one place so views never iterate raw config.
 */
final class Countries
{
    /** @return array<string, array{name: string, dial: string}> */
    public static function all(): array
    {
        /** @var array<string, array{name: string, dial: string}> */
        return config('countries', []);
    }

    /**
     * ISO code => display name, for a select.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(fn (array $c): string => $c['name'], self::all());
    }

    /**
     * ISO code => dialling code, consumed by the phone field's JS.
     *
     * @return array<string, string>
     */
    public static function dialCodes(): array
    {
        return array_map(fn (array $c): string => $c['dial'], self::all());
    }

    /**
     * Distinct dialling codes, labelled, for the prefix select.
     *
     * @return array<string, string>
     */
    public static function dialOptions(): array
    {
        $options = [];

        foreach (self::all() as $iso => $country) {
            // Several countries share a code (+1, +7); label it once.
            $options[$country['dial']] ??= $country['dial'];
        }

        uksort($options, static fn (string $a, string $b): int => (int) ltrim($a, '+') <=> (int) ltrim($b, '+'));

        return $options;
    }

    public static function name(?string $iso): ?string
    {
        return $iso === null ? null : (self::all()[$iso]['name'] ?? null);
    }

    public static function dial(?string $iso): ?string
    {
        return $iso === null ? null : (self::all()[$iso]['dial'] ?? null);
    }

    public static function exists(?string $iso): bool
    {
        return $iso !== null && array_key_exists($iso, self::all());
    }
}
