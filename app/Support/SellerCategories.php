<?php

namespace App\Support;

/**
 * Seller trading categories and their specialties.
 *
 * "Both" is derived rather than duplicated, so a specialty is only ever
 * defined once in config.
 */
final class SellerCategories
{
    public const BOTH = 'both';

    /** @return array<string, array{label: string, specialties: array<string, string>}> */
    public static function all(): array
    {
        /** @var array<string, array{label: string, specialties: array<string, string>}> $categories */
        $categories = config('verum_catalog.categories', []);

        if (isset($categories[self::BOTH])) {
            $categories[self::BOTH]['specialties'] = self::merged($categories);
        }

        return $categories;
    }

    /**
     * key => label, for the category select.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(fn (array $c): string => $c['label'], self::all());
    }

    /** @return array<string, string> */
    public static function specialtiesFor(?string $category): array
    {
        return self::all()[$category]['specialties'] ?? [];
    }

    /**
     * Every valid specialty key, for validation.
     *
     * @return list<string>
     */
    public static function allSpecialtyKeys(): array
    {
        return array_keys(self::merged(self::rawCategories()));
    }

    public static function label(?string $category): ?string
    {
        return self::all()[$category]['label'] ?? null;
    }

    /**
     * Turns stored keys back into readable labels.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function labelsFor(array $keys): array
    {
        $all = self::merged(self::rawCategories());

        return array_values(array_filter(array_map(
            fn (string $key): ?string => $all[$key] ?? null,
            $keys,
        )));
    }

    /**
     * @param  array<string, array{label: string, specialties: array<string, string>}>  $categories
     * @return array<string, string>
     */
    private static function merged(array $categories): array
    {
        $merged = [];

        foreach ($categories as $key => $category) {
            if ($key === self::BOTH) {
                continue;
            }

            $merged = [...$merged, ...$category['specialties']];
        }

        return $merged;
    }

    /** @return array<string, array{label: string, specialties: array<string, string>}> */
    private static function rawCategories(): array
    {
        /** @var array<string, array{label: string, specialties: array<string, string>}> */
        return config('verum_catalog.categories', []);
    }
}
