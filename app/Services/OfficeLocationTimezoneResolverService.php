<?php

namespace App\Services;

use App\Data\OfficeLocation\OfficeLocationTimezoneResolutionData;
use DateTimeZone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OfficeLocationTimezoneResolverService
{
    private const GOOGLE_TIMEZONE_API_URL = 'https://maps.googleapis.com/maps/api/timezone/json';

    public function resolve(float $latitude, float $longitude): OfficeLocationTimezoneResolutionData
    {
        if (! $this->coordinatesAreValid($latitude, $longitude)) {
            return OfficeLocationTimezoneResolutionData::unresolved(
                message: 'The selected coordinates are invalid.',
                source: 'validation'
            );
        }

        $serverKey = (string) config('services.google_maps.server_key', '');

        if ($serverKey === '') {
            return OfficeLocationTimezoneResolutionData::unresolved(
                message: 'Timezone lookup is not configured yet. Enter the office timezone manually.',
                source: 'configuration'
            );
        }

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->retry(1, 200)
                ->get(self::GOOGLE_TIMEZONE_API_URL, [
                    'location' => sprintf('%.7F,%.7F', $latitude, $longitude),
                    'timestamp' => now()->timestamp,
                    'key' => $serverKey,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Office location timezone lookup connection failed.', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $exception->getMessage(),
            ]);

            return OfficeLocationTimezoneResolutionData::unresolved(
                message: 'Timezone lookup is temporarily unavailable. Enter the timezone manually if needed.',
                source: 'connection_error'
            );
        }

        if (! $response->successful()) {
            Log::warning('Office location timezone lookup returned an unexpected HTTP status.', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return OfficeLocationTimezoneResolutionData::unresolved(
                message: 'Timezone lookup is temporarily unavailable. Enter the timezone manually if needed.',
                source: 'http_error'
            );
        }

        $payload = $response->json();
        $status = strtoupper((string) ($payload['status'] ?? ''));
        $timezone = trim((string) ($payload['timeZoneId'] ?? ''));
        $providerMessage = trim((string) ($payload['errorMessage'] ?? ''));

        if ($status === 'OK' && $this->timezoneIsValid($timezone)) {
            return OfficeLocationTimezoneResolutionData::resolved($timezone);
        }

        if ($status === 'ZERO_RESULTS') {
            return OfficeLocationTimezoneResolutionData::unresolved(
                message: 'No timezone could be determined for the selected point. Choose a more precise point on land or enter the timezone manually.',
                source: 'no_results'
            );
        }

        Log::warning('Office location timezone lookup returned a provider error.', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'provider_status' => $status,
            'provider_message' => $providerMessage,
            'provider_timezone' => $timezone,
        ]);

        return match ($status) {
            'REQUEST_DENIED' => OfficeLocationTimezoneResolutionData::unresolved(
                message: 'Google Time Zone API rejected the server key. Check Time Zone API enablement, billing, and server-key restrictions.',
                source: 'provider_denied'
            ),
            'OVER_DAILY_LIMIT', 'OVER_QUERY_LIMIT' => OfficeLocationTimezoneResolutionData::unresolved(
                message: 'Google Time Zone API quota or billing limit was reached. Check billing and quota for the server key.',
                source: 'provider_quota'
            ),
            'INVALID_REQUEST' => OfficeLocationTimezoneResolutionData::unresolved(
                message: 'Google Time Zone API rejected the request. Verify the selected coordinates and try again.',
                source: 'provider_invalid_request'
            ),
            default => OfficeLocationTimezoneResolutionData::unresolved(
                message: 'Automatic timezone detection failed. Enter the timezone manually or try again.',
                source: 'provider_error'
            ),
        };
    }

    private function coordinatesAreValid(float $latitude, float $longitude): bool
    {
        if ($latitude < -90 || $latitude > 90) {
            return false;
        }

        if ($longitude < -180 || $longitude > 180) {
            return false;
        }

        return ! ($latitude === 0.0 && $longitude === 0.0);
    }

    private function timezoneIsValid(string $timezone): bool
    {
        if ($timezone === '') {
            return false;
        }

        try {
            new DateTimeZone($timezone);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
