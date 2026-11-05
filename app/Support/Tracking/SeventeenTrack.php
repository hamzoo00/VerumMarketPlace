<?php

namespace App\Support\Tracking;

use App\Contracts\ParcelTracker;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * 17TRACK's tracking API (TRACKING_DRIVER=17track).
 *
 * Chosen for reach rather than polish: 3,000+ carriers including the
 * national posts a stone leaves Colombo or Bangkok with, and it detects
 * the carrier from the number when the seller picked "another carrier".
 *
 * A number is registered once (that is what the quota counts); after
 * that 17TRACK pushes every change to the webhook, and `fetch` reads the
 * same data for the poll that covers a missed push.
 *
 * Its statuses are translated here, so nothing past this class knows
 * which provider answered.
 */
final class SeventeenTrack implements ParcelTracker
{
    /** 17TRACK's answer for a number it is already watching. */
    private const ALREADY_REGISTERED = -18019901;

    public function watches(): bool
    {
        return true;
    }

    public function register(string $number, ?int $carrier): bool
    {
        $response = $this->send('register', [$this->parcel($number, $carrier)]);

        foreach ((array) $response->json('data.rejected', []) as $rejected) {
            $code = (int) data_get($rejected, 'error.code');

            if ($code === self::ALREADY_REGISTERED) {
                return true;
            }

            // A number it cannot read, or a carrier it cannot find: asking
            // again will not change the answer.
            Log::info('17TRACK would not watch a tracking number.', [
                'number' => $number,
                'code' => $code,
                'message' => data_get($rejected, 'error.message'),
            ]);

            return false;
        }

        return true;
    }

    public function fetch(array $parcels): array
    {
        if ($parcels === []) {
            return [];
        }

        $response = $this->send('gettrackinfo', array_map(
            fn (array $parcel): array => $this->parcel($parcel['number'], $parcel['carrier']),
            $parcels,
        ));

        return array_values(collect((array) $response->json('data.accepted', []))
            ->filter(fn (mixed $item): bool => is_array($item) && is_string($item['number'] ?? null))
            ->map(fn (array $item): ParcelReport => $this->report($item))
            ->all());
    }

    /**
     * 17TRACK signs a push as sha256(body + "/" + key), hex, in `sign`.
     *
     * The body is hashed exactly as it arrived: decoding and re-encoding
     * it changes the bytes and the signature no longer matches.
     */
    public function verifyWebhook(Request $request): bool
    {
        $key = (string) config('services.seventeentrack.key');
        $signature = (string) $request->header('sign');

        if ($key === '') {
            Log::error('A 17TRACK webhook arrived but SEVENTEENTRACK_KEY is not set.');

            return false;
        }

        return $signature !== ''
            && hash_equals(hash('sha256', $request->getContent().'/'.$key), Str::lower($signature));
    }

    public function parseWebhook(array $payload): array
    {
        // TRACKING_STOPPED only says no more pushes will come; the poll
        // keeps asking until the order is delivered or too old.
        if (($payload['event'] ?? null) !== 'TRACKING_UPDATED' || ! is_array($payload['data'] ?? null)) {
            return [];
        }

        $data = $payload['data'];

        return is_string($data['number'] ?? null) ? [$this->report($data)] : [];
    }

    /** @param  array<string, mixed>  $item */
    private function report(array $item): ParcelReport
    {
        $info = is_array($item['track_info'] ?? null) ? $item['track_info'] : [];
        $latest = $this->status(
            $this->text(data_get($info, 'latest_status.status')),
            $this->text(data_get($info, 'latest_status.sub_status')),
        );

        $scans = collect((array) data_get($info, 'tracking.providers', []))
            ->flatMap(fn (mixed $provider): array => is_array($provider) ? (array) ($provider['events'] ?? []) : [])
            ->map(fn (mixed $event): ?array => is_array($event) ? $this->event($event) : null)
            ->filter()
            ->sortByDesc(fn (array $event): int => $event['at']->getTimestamp())
            ->values()
            ->map(fn (array $event, int $index): CourierScan => new CourierScan(
                // An event 17TRACK has not classified is still movement; the
                // newest takes the parcel's overall status, which it has.
                $index === 0 && $latest !== null && $latest !== 'stalled'
                    ? $latest
                    : ($event['status'] ?? 'in_transit'),
                $event['description'],
                $event['location'],
                $event['at'],
            ))
            ->all();

        // "Expired": nothing scanned for weeks. Recorded once, so the seller
        // hears of it, without inventing a scan time.
        if ($latest === 'stalled') {
            $scans[] = new CourierScan('stalled', null, null, CarbonImmutable::now(), 'stalled:'.$item['number']);
        }

        return new ParcelReport(Str::upper((string) $item['number']), array_values($scans));
    }

    /**
     * @param  array<mixed>  $event
     * @return array{status: string|null, description: string|null, location: string|null, at: CarbonImmutable}|null
     */
    private function event(array $event): ?array
    {
        $time = $this->text($event['time_utc'] ?? null) ?? $this->text($event['time_iso'] ?? null);

        if ($time === null) {
            return null;
        }

        try {
            $at = CarbonImmutable::parse($time)->utc();
        } catch (Throwable) {
            return null;
        }

        $address = is_array($event['address'] ?? null) ? $event['address'] : [];
        $location = $this->text($event['location'] ?? null)
            ?? $this->text(collect([$address['city'] ?? null, $address['state'] ?? null, $address['country'] ?? null])
                ->filter(fn (mixed $part): bool => is_string($part) && $part !== '')
                ->implode(', '));

        return [
            'status' => $this->status($this->text($event['stage'] ?? null), $this->text($event['sub_status'] ?? null)),
            'description' => $this->text($event['description'] ?? null),
            'location' => $location,
            'at' => $at,
        ];
    }

    /**
     * 17TRACK's status and sub-status, in this app's words. Null when it
     * says nothing useful (NotFound, or an event it has not classified).
     */
    private function status(?string $stage, ?string $sub): ?string
    {
        $sub ??= '';

        // Gem parcels sit in customs more than most; worth its own step.
        if (str_starts_with($sub, 'InTransit_Customs')) {
            return 'customs';
        }

        if (in_array($sub, ['Exception_Returning', 'Exception_Returned'], true)) {
            return 'returned';
        }

        return match ($sub !== '' ? Str::before($sub, '_') : $stage) {
            'InfoReceived' => 'info_received',
            'InTransit', 'PickedUp', 'Departure', 'Arrival' => 'in_transit',
            'OutForDelivery' => 'out_for_delivery',
            'AvailableForPickup' => 'available_for_pickup',
            'DeliveryFailure' => 'delivery_failed',
            'Delivered' => 'delivered',
            'Exception' => 'exception',
            'Returning', 'Returned' => 'returned',
            'Expired' => 'stalled',
            default => null,
        };
    }

    /** @return array{number: string, carrier?: int} */
    private function parcel(string $number, ?int $carrier): array
    {
        return $carrier === null ? ['number' => $number] : ['number' => $number, 'carrier' => $carrier];
    }

    /**
     * @param  list<array<string, mixed>>  $body
     *
     * @throws TrackingFailed
     */
    private function send(string $endpoint, array $body): Response
    {
        try {
            $response = $this->request()->post($endpoint, $body);
        } catch (ConnectionException $e) {
            Log::warning('17TRACK could not be reached.', ['error' => $e->getMessage()]);

            throw new TrackingFailed('The tracking service could not be reached.', previous: $e);
        }

        if (! $response->successful() || (int) $response->json('code', -1) !== 0) {
            Log::warning('17TRACK refused a request.', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'code' => $response->json('code'),
            ]);

            throw new TrackingFailed('The tracking service refused the request.');
        }

        return $response;
    }

    private function request(): PendingRequest
    {
        $key = (string) config('services.seventeentrack.key');

        if ($key === '') {
            Log::error('TRACKING_DRIVER is 17track but SEVENTEENTRACK_KEY is not set.');

            throw new TrackingFailed('The tracking service is not configured.');
        }

        return Http::baseUrl(rtrim((string) config('verum_tracking.17track.url'), '/').'/')
            ->withHeaders(['17token' => $key])
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
