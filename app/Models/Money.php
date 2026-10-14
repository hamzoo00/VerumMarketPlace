<?php

namespace App\Models;

use NumberFormatter;
use Stringable;

/**
 * Money is stored as integer minor units everywhere; this value object is the
 * single place that turns cents into something a view can print.
 */
final readonly class Money implements Stringable
{
    public function __construct(
        public int $cents,
        public string $currency = 'USD',
    ) {}

    public static function fromCents(int $cents, string $currency = 'USD'): self
    {
        return new self($cents, $currency);
    }

    public function amount(): float
    {
        return $this->cents / 100;
    }

    /** Whole amounts print without decimals, matching the demo's "$680". */
    public function format(?string $locale = null): string
    {
        $formatter = new NumberFormatter($locale ?? app()->getLocale(), NumberFormatter::CURRENCY);
        $formatter->setAttribute(
            NumberFormatter::FRACTION_DIGITS,
            $this->cents % 100 === 0 ? 0 : 2,
        );

        return (string) $formatter->formatCurrency($this->amount(), $this->currency);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
