<?php

namespace App\Support;

/**
 * The vocabulary a listing's gem details are written in.
 *
 * One reader for config/verum_gems.php, so the dropdowns, ListingRequest
 * and the browser's validator cannot each keep their own list. A pattern
 * is handed out in two forms — a server `regex:` and an HTML `pattern` —
 * from the same body, which is what keeps the two checks in agreement.
 */
final class GemSpec
{
    /** @return array<string, string> value => label, for a select */
    public static function shapes(): array
    {
        return self::options(self::list('verum_gems.shapes'));
    }

    /** @return array<string, string> */
    public static function cuttingStyles(): array
    {
        return self::options(self::list('verum_gems.cutting_styles'));
    }

    /**
     * Grouped for the dropdown: coloured-stone terms, then diamond grades.
     *
     * @return array<string, array<string, string>>
     */
    public static function clarityGroups(): array
    {
        $groups = [];

        foreach (self::clarity() as $group => $grades) {
            $groups[(string) __($group)] = self::options($grades);
        }

        return $groups;
    }

    /** @return list<string> every clarity term, for validation */
    public static function clarityGrades(): array
    {
        return array_merge(...array_values(self::clarity()));
    }

    /** @return array<string, string> */
    public static function labs(): array
    {
        return self::options(self::list('verum_gems.labs'));
    }

    /** The body, for an HTML `pattern` attribute. */
    public static function pattern(string $name): string
    {
        return (string) config("verum_gems.patterns.{$name}");
    }

    /** The same body anchored and delimited, for a `regex:` rule. */
    public static function regex(string $name): string
    {
        return '~^(?:'.self::pattern($name).')$~u';
    }

    public static function caratMax(): int
    {
        return (int) config('verum_gems.limits.carat_max');
    }

    /**
     * "8.1x6.4x4.2" and "8,1 × 6,4 × 4,2 mm" are the same measurement;
     * store them one way so listings read alike.
     */
    public static function normaliseDimensions(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        preg_match_all('/\d+(?:[.,]\d+)?/', $value, $matches);

        return implode(' × ', array_map(
            fn (string $figure): string => str_replace(',', '.', $figure),
            $matches[0],
        )).' mm';
    }

    /**
     * @param  list<string>  $values
     * @return array<string, string>
     */
    private static function options(array $values): array
    {
        $options = [];

        foreach ($values as $value) {
            $options[$value] = (string) __($value);
        }

        return $options;
    }

    /** @return list<string> */
    private static function list(string $key): array
    {
        /** @var list<string> $values */
        $values = config($key, []);

        return $values;
    }

    /** @return array<string, list<string>> */
    private static function clarity(): array
    {
        /** @var array<string, list<string>> $groups */
        $groups = config('verum_gems.clarity', []);

        return $groups;
    }
}
