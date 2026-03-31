<?php

namespace Tests\Feature\Web\Admin;

use App\Enums\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceSettingsPageTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_admin_attendance_settings_page_renders_real_database_values(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);

        $office = $this->createOfficeLocation([
            'code' => 'YGY',
            'name' => 'Yogyakarta Office',
            'address' => 'Jl. Malioboro No. 10, Yogyakarta',
            'radius_meter' => 120,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $this->createAttendanceSetting($office, [
            'work_start_time' => '07:30:00',
            'work_end_time' => '16:45:00',
            'late_tolerance_minutes' => 22,
            'qr_rotation_seconds' => 45,
            'min_location_accuracy_meter' => 35,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->get(route('admin.attendance.settings', ['office_location_id' => $office->id]));

        $response->assertOk()
            ->assertSee('Yogyakarta Office', false)
            ->assertSee('Jl. Malioboro No. 10, Yogyakarta', false)
            ->assertSee('name="work_start_time_hour"', false)
            ->assertSee('name="work_start_time_minute"', false)
            ->assertSee('name="work_end_time_hour"', false)
            ->assertSee('name="work_end_time_minute"', false)
            ->assertSee('id="work_start_time_hour"', false)
            ->assertSee('id="work_end_time_minute"', false)
            ->assertSee('value="22"', false)
            ->assertSee('value="45"', false)
            ->assertSee('value="35"', false)
            ->assertSee('120 m', false)
            ->assertSee('Asia/Jakarta', false);
    }

    public function test_admin_can_update_attendance_settings_for_selected_office(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);

        $office = $this->createOfficeLocation([
            'name' => 'Semarang Office',
        ]);

        $setting = $this->createAttendanceSetting($office, [
            'work_start_time' => '09:00:00',
            'work_end_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'qr_rotation_seconds' => 30,
            'min_location_accuracy_meter' => 50,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->put(route('admin.attendance.settings.update'), [
                'office_location_id' => $office->id,
                'work_start_time_hour' => '08',
                'work_start_time_minute' => '15',
                'work_end_time_hour' => '17',
                'work_end_time_minute' => '30',
                'late_tolerance_minutes' => 20,
                'qr_rotation_seconds' => 60,
                'min_location_accuracy_meter' => 25,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.attendance.settings', ['office_location_id' => $office->id]));

        $this->assertDatabaseHas('attendance_settings', [
            'id' => $setting->id,
            'office_location_id' => $office->id,
            'work_start_time' => '08:15',
            'work_end_time' => '17:30',
            'late_tolerance_minutes' => 20,
            'qr_rotation_seconds' => 60,
            'min_location_accuracy_meter' => 25,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_attendance_settings_page_uses_same_real_data_view(): void
    {
        $superAdmin = $this->createEmployee();
        $this->assignRole($superAdmin, Roles::SuperAdmin->value);

        $office = $this->createOfficeLocation([
            'code' => 'SBY',
            'name' => 'Surabaya Office',
            'address' => 'Jl. Tunjungan No. 88, Surabaya',
            'radius_meter' => 90,
            'timezone' => 'Asia/Jakarta',
        ]);

        $this->createAttendanceSetting($office, [
            'work_start_time' => '08:00:00',
            'work_end_time' => '17:00:00',
            'late_tolerance_minutes' => 10,
            'qr_rotation_seconds' => 30,
            'min_location_accuracy_meter' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->get(route('super-admin.attendance.settings', ['office_location_id' => $office->id]));

        $response->assertOk()
            ->assertSee('Surabaya Office', false)
            ->assertSee('Jl. Tunjungan No. 88, Surabaya', false)
            ->assertSee('name="work_start_time_hour"', false)
            ->assertSee('name="work_end_time_minute"', false)
            ->assertSee('90 m', false);
    }

    public function test_admin_can_still_update_attendance_settings_with_legacy_time_payload(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);

        $office = $this->createOfficeLocation([
            'name' => 'Legacy Office',
        ]);

        $setting = $this->createAttendanceSetting($office, [
            'work_start_time' => '09:00:00',
            'work_end_time' => '17:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->put(route('admin.attendance.settings.update'), [
                'office_location_id' => $office->id,
                'work_start_time' => '10:00',
                'work_end_time' => '18:00',
                'late_tolerance_minutes' => 15,
                'qr_rotation_seconds' => 30,
                'min_location_accuracy_meter' => 50,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.attendance.settings', ['office_location_id' => $office->id]));

        $this->assertDatabaseHas('attendance_settings', [
            'id' => $setting->id,
            'work_start_time' => '10:00',
            'work_end_time' => '18:00',
        ]);
    }
}

