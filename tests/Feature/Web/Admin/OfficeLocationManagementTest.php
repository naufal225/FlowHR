<?php

namespace Tests\Feature\Web\Admin;

use App\Enums\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class OfficeLocationManagementTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_admin_create_page_renders_google_maps_office_location_experience(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);
        config()->set('services.google_maps.browser_key', 'browser-key');

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->get(route('admin.office-locations.create'));

        $response->assertOk()
            ->assertSee('Find and Pin the Office', false)
            ->assertSee('Most office geofences work best between 50-300 meters.', false)
            ->assertSee('Quick Slider', false)
            ->assertSee('Candidate locations', false)
            ->assertSee('Places API (New)', false)
            ->assertSee('fetchAutocompleteSuggestions', false)
            ->assertSee('locationBias', false)
            ->assertDontSee('PlaceAutocompleteElement', false)
            ->assertDontSee('locationRestriction = bounds', false)
            ->assertDontSee('google.maps.places.Autocomplete', false)
            ->assertDontSee('leaflet', false);
    }

    public function test_admin_can_store_an_office_location_with_timezone(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->post(route('admin.office-locations.store'), [
                'code' => 'JKT-HQ',
                'name' => 'Jakarta Head Office',
                'address' => 'Jl. Jend. Sudirman No. 1',
                'latitude' => -6.2000000,
                'longitude' => 106.8166667,
                'radius_meter' => 120,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.office-locations.index'));

        $this->assertDatabaseHas('office_locations', [
            'code' => 'JKT-HQ',
            'name' => 'Jakarta Head Office',
            'timezone' => 'Asia/Jakarta',
            'radius_meter' => 120,
        ]);
    }

    public function test_admin_store_requires_a_valid_timezone(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);

        $response = $this->from(route('admin.office-locations.create'))
            ->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->post(route('admin.office-locations.store'), [
                'code' => 'SBY-HQ',
                'name' => 'Surabaya Office',
                'address' => 'Jl. Basuki Rahmat No. 10',
                'latitude' => -7.2575000,
                'longitude' => 112.7521000,
                'radius_meter' => 150,
                'timezone' => 'Invalid/Timezone',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.office-locations.create'))
            ->assertSessionHasErrors(['timezone']);
    }

    public function test_admin_can_resolve_timezone_from_coordinates(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);
        config()->set('services.google_maps.server_key', 'server-key');

        Http::fake([
            'https://maps.googleapis.com/maps/api/timezone/json*' => Http::response([
                'status' => 'OK',
                'timeZoneId' => 'Asia/Jakarta',
            ]),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->postJson(route('admin.office-locations.resolve-timezone'), [
                'latitude' => -6.2000000,
                'longitude' => 106.8166667,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'resolved' => true,
                    'timezone' => 'Asia/Jakarta',
                    'source' => 'google_maps',
                ],
            ]);
    }
}

