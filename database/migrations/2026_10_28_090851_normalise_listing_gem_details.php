<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Brings existing listings into line with the gem-detail vocabulary.
 *
 * Shape, cutting style, clarity and lab became choices from a list. Rows
 * written before that hold free text: a cut written as "Oval, brilliant"
 * keeps its style, a shape or colour of "22" is dropped, because a value
 * the form can no longer show would make the listing impossible to save.
 *
 * The lists are copied here rather than read from config: a migration has
 * to do the same thing in a year as it does today.
 *
 * One-way. Nothing dropped was a real description.
 */
return new class extends Migration
{
    private const SHAPES = [
        'Round', 'Oval', 'Cushion', 'Emerald cut', 'Pear', 'Marquise', 'Heart',
        'Princess', 'Radiant', 'Asscher', 'Trillion', 'Baguette', 'Octagon',
        'Square', 'Cabochon', 'Briolette', 'Freeform', 'Rough',
    ];

    private const STYLES = [
        'Brilliant', 'Step', 'Mixed', 'Checkerboard', 'Cabochon', 'Rose',
        'Portuguese', 'Precision', 'Carved', 'Fantasy', 'Uncut',
    ];

    private const CLARITY = [
        'Loupe clean', 'Eye clean', 'Very slightly included', 'Slightly included',
        'Included', 'Heavily included', 'FL', 'IF', 'VVS1', 'VVS2', 'VS1', 'VS2',
        'SI1', 'SI2', 'I1', 'I2', 'I3',
    ];

    private const LABS = [
        'GIA', 'AGL', 'GRS', 'Gübelin', 'SSEF', 'Lotus Gemology', 'AIGS', 'GIT',
        'IGI', 'HRD', 'CGL',
    ];

    public function up(): void
    {
        DB::table('listings')
            ->select(['id', 'shape', 'cut', 'clarity', 'color', 'treatment', 'origin', 'dimensions', 'lab'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $clean = [
                        'shape' => $this->pick($row->shape, self::SHAPES),
                        'cut' => $this->style($row->cut),
                        'clarity' => $this->pick($row->clarity, self::CLARITY),
                        'color' => $this->words($row->color),
                        'treatment' => $this->words($row->treatment),
                        'origin' => $this->words($row->origin),
                        'dimensions' => $this->dimensions($row->dimensions),
                        'lab' => $this->lab($row->lab),
                    ];

                    $changed = array_filter(
                        $clean,
                        fn (?string $value, string $column): bool => $value !== $row->{$column},
                        ARRAY_FILTER_USE_BOTH,
                    );

                    if ($changed !== []) {
                        DB::table('listings')->where('id', $row->id)->update($changed);
                    }
                }
            });
    }

    public function down(): void
    {
        // Dropped values were not descriptions; there is nothing to restore.
    }

    /** @param  list<string>  $allowed */
    private function pick(?string $value, array $allowed): ?string
    {
        if ($value === null) {
            return null;
        }

        foreach ($allowed as $option) {
            if (mb_strtolower(trim($value)) === mb_strtolower($option)) {
                return $option;
            }
        }

        return null;
    }

    /** "Oval, brilliant" carried the outline and the style; keep the style. */
    private function style(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $parts = array_map('trim', explode(',', $value));

        return $this->pick(end($parts) ?: null, self::STYLES)
            ?? $this->pick($value, self::STYLES);
    }

    private function words(?string $value): ?string
    {
        return $value !== null && preg_match('/\p{L}/u', $value) === 1 ? $value : null;
    }

    private function dimensions(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        preg_match_all('/\d+(?:[.,]\d+)?/', $value, $matches);
        $figures = $matches[0];

        if (count($figures) < 2 || count($figures) > 3) {
            return null;
        }

        return implode(' × ', array_map(fn (string $f): string => str_replace(',', '.', $f), $figures)).' mm';
    }

    private function lab(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $this->pick($value, self::LABS) ?? 'Other laboratory';
    }
};
