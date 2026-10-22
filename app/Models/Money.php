<?php

namespace App\Models;

use App\Support\DisplayCurrency;
use App\Support\ExchangeRates;
use NumberFormatter;
use Stringable;

/**
 * Money is stored as integer minor units everywhere; this value object is the
 * single place that turns cents into something a view can print.
 *
 * Because every price goes through here, the visitor's chosen currency is
 * added here too: `format()` keeps printing the charged USD figure, and
 * `withLocal()` appends their own currency in brackets. Doing it in the
 * views instead would mean one more place to forget.
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

        /*
         * ICU knows each currency's real precision — yen and won have no
         * minor unit, dinars have three — so only drop the decimals on a
         * round amount, and never add any the currency does not have.
         */
        if ($this->cents % 100 === 0) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
        }

        return (string) $formatter->formatCurrency($this->amount(), $this->currency);
    }

    /**
     * The charged amount, plus the visitor's currency in brackets.
     *
     * The USD figure always leads, because that is what is actually
     * charged — the bracketed one is a courtesy and must never be
     * mistaken for the price. Returns `format()` unchanged when no
     * country is chosen, the visitor is already on the base currency, or
     * no usable rate exists.
     */
    public function withLocal(?string $locale = null): string
    {
        $converted = $this->local();

        return $converted === null
            ? $this->format($locale)
            : $this->format($locale).' ('.$converted->format($locale).')';
    }

    /**
     * This amount in the visitor's currency, or null if it cannot be shown.
     *
     * Converted from the stored cents rather than from a formatted
     * string, so rounding happens once, at the end.
     */
    public function local(): ?Money
    {
        $currency = DisplayCurrency::currency();

        if ($currency === null || $this->currency !== ExchangeRates::base()) {
            return null;
        }

        $rate = ExchangeRates::for($currency);

        if ($rate === null) {
            return null;
        }

        /*
         * Rounded to a whole unit. A conversion is an estimate, and
         * printing "LKR 331,602.92" claims a precision the rate does not
         * have — the figure moves by more than a cent by tomorrow.
         */
        $converted = (int) round(($this->cents * $rate) / 100) * 100;

        return new self($converted, $currency);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
