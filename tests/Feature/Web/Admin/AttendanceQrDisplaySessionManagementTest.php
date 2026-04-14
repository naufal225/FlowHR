<?php

namespace Tests\Feature\Web\Admin;

use App\Enums\Roles;
use App\Models\AttendanceQrDisplaySession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceQrDisplaySessionManagementTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_admin_can_create_qr_display_session_and_receive_flash_url(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);
        $office = $this->createOfficeLocation();

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->post(route('admin.attendance.qr.display-sessions.store'), [
                'office_location_id' => $office->id,
                'name' => 'Lobby TV 1',
                'ttl_days' => 30,
            ]);

        $response->assertRedirect(route('admin.attendance.qr', ['office_location_id' => $office->id]))
            ->assertSessionHas('attendance_qr_display_url')
            ->assertSessionMissing('attendance_qr_display_status_url');

        $this->assertDatabaseHas('attendance_qr_display_sessions', [
            'office_location_id' => $office->id,
            'name' => 'Lobby TV 1',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_revoke_qr_display_session(): void
    {
        $admin = $this->createEmployee();
        $this->assignRole($admin, Roles::Admin->value);
        $office = $this->createOfficeLocation();

        $session = AttendanceQrDisplaySession::query()->create([
            'office_location_id' => $office->id,
            'name' => 'Reception TV',
            'token_hash' => hash('sha256', 'secret'),
            'expires_at' => now()->addDay(),
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->post(route('admin.attendance.qr.display-sessions.revoke', $session->id), [
                'office_location_id' => $office->id,
            ]);

        $response->assertRedirect(route('admin.attendance.qr', ['office_location_id' => $office->id]));

        $session->refresh();
        $this->assertNotNull($session->revoked_at);
    }
}
