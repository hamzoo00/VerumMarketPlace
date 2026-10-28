<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

/**
 * The switches an admin can throw, read as the right type.
 *
 * One place, so a controller, a Blade view and an action cannot disagree
 * about whether commission is on. The shape — key, type, default, bounds
 * — comes from config/verum_platform.php; the live values from the
 * database.
 *
 * Reads are cached for the request and beyond, because `commission()` is
 * asked on every order and `approvalRequired()` on every publish. A write
 * clears the cache, so a toggle takes effect immediately rather than when
 * a TTL happens to expire.
 */
final class PlatformSettings
{
    private const CACHE_KEY = 'verum:platform:settings';

    /** @return array<string, mixed> */
    public static function all(): array
    {
        /** @var array<string, mixed> */
        return Cache::rememberForever(self::CACHE_KEY, static function (): array {
            $stored = PlatformSetting::query()->pluck('value', 'key')->all();
            $values = [];

            foreach (self::schema() as $key => $rule) {
                $values[$key] = array_key_exists($key, $stored)
                    ? self::cast($stored[$key], $rule)
                    : $rule['default'];
            }

            return $values;
        });
    }

    public static function get(string $key): mixed
    {
        return self::all()[$key] ?? self::schema()[$key]['default'] ?? null;
    }

    /**
     * Write several at once, as one form save.
     *
     * Values are clamped to the schema's bounds here rather than trusted
     * from the request: a commission of 900% would otherwise be one
     * typo away.
     *
     * @param  array<string, mixed>  $values
     */
    public static function put(array $values, ?int $adminId = null): void
    {
        foreach ($values as $key => $value) {
            $rule = self::schema()[$key] ?? null;

            if ($rule === null) {
                continue;
            }

            PlatformSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => self::serialise(self::clamp(self::cast($value, $rule), $rule)),
                    'updated_by' => $adminId,
                ],
            );
        }

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Is a cut taken from each sale? */
    public static function commissionEnabled(): bool
    {
        return (bool) self::get('commission_enabled');
    }

    /**
     * The rate to apply right now, or zero when it is switched off.
     *
     * Zero rather than null so callers can multiply without branching —
     * and a rate of zero with the toggle on is a real configuration, not
     * an error.
     */
    public static function commissionRate(): float
    {
        return self::commissionEnabled() ? (float) self::get('commission_percent') : 0.0;
    }

    /** Must a listing be approved before it goes public? */
    public static function approvalRequired(): bool
    {
        return (bool) self::get('listing_approval_required');
    }

    public static function featuringEnabled(): bool
    {
        return (bool) self::get('featuring_enabled');
    }

    public static function featuredSlots(): int
    {
        return (int) self::get('featured_slots');
    }

    /** @return array<string, array<string, mixed>> */
    public static function schema(): array
    {
        /** @var array<string, array<string, mixed>> */
        return config('verum_platform.settings', []);
    }

    /** @param  array<string, mixed>  $rule */
    private static function cast(mixed $value, array $rule): mixed
    {
        return match ($rule['type']) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            'int' => (int) $value,
            'float' => (float) $value,
            default => (string) $value,
        };
    }

    /** @param  array<string, mixed>  $rule */
    private static function clamp(mixed $value, array $rule): mixed
    {
        if (! is_int($value) && ! is_float($value)) {
            return $value;
        }

        if (isset($rule['min'])) {
            $value = max($rule['min'], $value);
        }

        if (isset($rule['max'])) {
            $value = min($rule['max'], $value);
        }

        return $value;
    }

    private static function serialise(mixed $value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }
}
