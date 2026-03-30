<?php

namespace Tests\Unit;

use App\Services\OfficeLocationTimezoneResolverService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OfficeLocationTimezoneResolverServiceTest extends TestCase
{
    public function test_it_resolves_timezone_from_google_maps_api(): void
    {
        config()->set('services.google_maps.server_key', 'server-key');

        Http::fake([
            'https://maps.googleapis.com/maps/api/timezone/json*' => Http::response([
                'status' => 'OK',
                'timeZoneId' => 'Asia/Jakarta',
            ]),
        ]);

        $service = app(OfficeLocationTimezoneResolverService::class);
        $result = $service->resolve(-6.2000000, 106.8166667);

        $this->assertTrue($result->resolved);
        $this->assertSame('Asia/Jakarta', $result->timezone);
        $this->assertSame('google_maps', $result->source);
    }

    public function test_it_returns_unresolved_when_server_key_is_missing(): void
    {
        config()->set('services.google_maps.server_key', null);

        $service = app(OfficeLocationTimezoneResolverService::class);
        $result = $service->resolve(-6.2000000, 106.8166667);

        $this->assertFalse($result->resolved);
        $this->assertSame('configuration', $result->source);
        $this->assertNull($result->timezone);
    }

    public function test_it_returns_actionable_message_when_provider_denies_request(): void
    {
        config()->set('services.google_maps.server_key', 'server-key');

        Http::fake([
            'https://maps.googleapis.com/maps/api/timezone/json*' => Http::response([
                'status' => 'REQUEST_DENIED',
                'errorMessage' => 'API key invalid.',
            ], 200),
        ]);

        $service = app(OfficeLocationTimezoneResolverService::class);
        $result = $service->resolve(-6.2000000, 106.8166667);

        $this->assertFalse($result->resolved);
        $this->assertSame('provider_denied', $result->source);
        $this->assertSame('Google Time Zone API rejected the server key. Check Time Zone API enablement, billing, and server-key restrictions.', $result->message);
        $this->assertNull($result->timezone);
    }
}
