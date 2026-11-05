<?php

namespace App\Support\Tracking;

/**
 * What a courier says about one tracking number: its scans, oldest or
 * newest first — order does not matter, each carries its own time.
 */
final readonly class ParcelReport
{
    /** @param  list<CourierScan>  $scans */
    public function __construct(
        public string $number,
        public array $scans,
    ) {}
}
