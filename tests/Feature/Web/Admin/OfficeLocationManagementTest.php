<?php

namespace Tests\Feature\Web\Admin;

use App\Enums\Roles;
use Carbon\Carbon;
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

    public function test_admin_can_view_office_location_detail_with_latest_first_paginated_employees(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);

        $officeLocation = $this->createOfficeLocation([
            'code' => 'HQ-JKT',
            'name' => 'Jakarta HQ',
            'address' => 'Jl. Jend. Sudirman No. 99, Jakarta',
            'timezone' => 'Asia/Jakarta',
            'radius_meter' => 150,
        ]);

        $division = $this->createDivision(['name' => 'Operations']);

        for ($index = 1; $index <= 12; $index++) {
            $label = str_pad((string) $index, 2, '0', STR_PAD_LEFT);

            $this->createEmployee([
                'name' => 'Employee ' . $label,
                'email' => 'employee' . $label . '@example.com',
                'created_at' => Carbon::create(2026, 1, 1, 8, 0, 0, 'Asia/Jakarta')->addDays($index),
                'updated_at' => Carbon::create(2026, 1, 1, 8, 0, 0, 'Asia/Jakarta')->addDays($index),
            ], $officeLocation, $division);
        }

        $otherOffice = $this->createOfficeLocation([
            'code' => 'SBY',
            'name' => 'Surabaya Branch',
        ]);

        $this->createEmployee([
            'name' => 'External Employee',
            'email' => 'external.employee@example.com',
        ], $otherOffice, $division);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->get(route('admin.office-locations.show', $officeLocation));

        $response->assertOk()
            ->assertSee('Office Detail', false)
            ->assertSee('Jakarta HQ', false)
            ->assertSee('12 total employees', false)
            ->assertSee('Asia/Jakarta', false)
            ->assertSeeInOrder(['Employee 12', 'Employee 11', 'Employee 10'])
            ->assertDontSee('Employee 01', false)
            ->assertDontSee('External Employee', false)
            ->assertSee('Showing 1-10 of 12 assigned employees.', false);
    }

    public function test_admin_office_location_detail_second_page_shows_remaining_employees(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);

        $officeLocation = $this->createOfficeLocation([
            'code' => 'BDG',
            'name' => 'Bandung Office',
        ]);

        $division = $this->createDivision(['name' => 'People Ops']);

        for ($index = 1; $index <= 12; $index++) {
            $label = str_pad((string) $index, 2, '0', STR_PAD_LEFT);

            $this->createEmployee([
                'name' => 'Paged Employee ' . $label,
                'email' => 'paged.employee' . $label . '@example.com',
                'created_at' => Carbon::create(2026, 2, 1, 9, 0, 0, 'Asia/Jakarta')->addDays($index),
                'updated_at' => Carbon::create(2026, 2, 1, 9, 0, 0, 'Asia/Jakarta')->addDays($index),
            ], $officeLocation, $division);
        }

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->get(route('admin.office-locations.show', $officeLocation) . '?page=2');

        $response->assertOk()
            ->assertSee('Paged Employee 02', false)
            ->assertSee('Paged Employee 01', false)
            ->assertDontSee('Paged Employee 12', false)
            ->assertSee('Showing 11-12 of 12 assigned employees.', false);
    }

    public function test_super_admin_can_view_office_location_detail_page(): void
    {
        $superAdmin = $this->createEmployee();
        $this->assignRole($superAdmin, Roles::SuperAdmin->value);

        $officeLocation = $this->createOfficeLocation([
            'code' => 'DPS',
            'name' => 'Denpasar Office',
        ]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->get(route('super-admin.office-locations.show', $officeLocation));

        $response->assertOk()
            ->assertSee('Denpasar Office', false)
            ->assertSee('Back to List', false)
            ->assertSee('Assigned Employees', false);
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

    public function test_admin_can_delete_office_location_and_unassign_employees(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);

        $officeLocation = $this->createOfficeLocation([
            'code' => 'BDG-HQ',
            'name' => 'Bandung Office',
        ]);

        $employeeOne = $this->createEmployee(['email' => 'bandung.one@example.com'], $officeLocation);
        $employeeTwo = $this->createEmployee(['email' => 'bandung.two@example.com'], $officeLocation);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->delete(route('admin.office-locations.destroy', $officeLocation));

        $response->assertRedirect(route('admin.office-locations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('office_locations', [
            'id' => $officeLocation->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $employeeOne->id,
            'office_location_id' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $employeeTwo->id,
            'office_location_id' => null,
        ]);
    }

    public function test_admin_cannot_delete_office_location_with_attendance_history(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);

        $officeLocation = $this->createOfficeLocation([
            'code' => 'SBY-HQ',
            'name' => 'Surabaya Office',
        ]);

        $employee = $this->createEmployee(['email' => 'surabaya.employee@example.com'], $officeLocation);
        $this->createAttendance($employee, $officeLocation);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->delete(route('admin.office-locations.destroy', $officeLocation));

        $response->assertRedirect(route('admin.office-locations.index'))
            ->assertSessionHas('error', 'Office location cannot be deleted because it already has attendance history.');

        $this->assertDatabaseHas('office_locations', [
            'id' => $officeLocation->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'office_location_id' => $officeLocation->id,
        ]);
    }
}
