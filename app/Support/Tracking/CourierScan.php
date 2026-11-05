<?php

namespace App\Support\Tracking;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * One scan, already in this app's status vocabulary.
 *
 * The fingerprint is what makes a courier report idempotent: providers
 * resend the whole history on every update, and the same scan must land
 * once however often it is reported.
 */
final readonly class CourierScan
{
    public function __construct(
        public string $status,
        public ?string $description,
        public ?string $location,
        public CarbonImmutable $occurredAt,
        public ?string $key = null,
    ) {}

    public function fingerprint(): string
    {
        return hash('sha256', $this->key ?? implode('|', [
            'courier',
            $this->occurredAt->utc()->toIso8601String(),
            Str::lower(trim((string) $this->description)),
            Str::lower(trim((string) $this->location)),
        ]));
    }
}
