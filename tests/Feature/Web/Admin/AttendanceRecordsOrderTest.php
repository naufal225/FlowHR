<?php

namespace Tests\Feature\Web\Admin;

use App\Enums\Roles;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceRecordsOrderTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_admin_attendance_records_are_listed_last_in_first_out(): void
    {
        $office = $this->createOfficeLocation([
            'name' => 'Admin Attendance Office',
        ]);
        $admin = $this->createEmployee([
            'name' => 'Admin Attendance Viewer',
        ], $office);
        $olderUser = $this->createEmployee([
            'name' => 'Older Attendance User',
        ], $office);
        $newerUser = $this->createEmployee([
            'name' => 'Newer Attendance User',
        ], $office);

        $this->assignRole($admin, Roles::Admin->value);
        $this->assignRole($olderUser, Roles::Employee->value);
        $this->assignRole($newerUser, Roles::Employee->value);

        $baseDate = Carbon::now('Asia/Jakarta')->startOfMonth();

        $olderRecord = $this->createAttendance($olderUser, $office, null, [
            'work_date' => $baseDate->copy()->addDays(10)->toDateString(),
            'created_at' => $baseDate->copy()->addDay()->setTime(8, 0),
            'updated_at' => $baseDate->copy()->addDay()->setTime(8, 0),
        ]);
        $newerRecord = $this->createAttendance($newerUser, $office, null, [
            'work_date' => $baseDate->copy()->addDays(2)->toDateString(),
            'created_at' => $baseDate->copy()->addDays(3)->setTime(9, 0),
            'updated_at' => $baseDate->copy()->addDays(3)->setTime(9, 0),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->get(route('admin.attendance.records'));

        $response->assertOk()
            ->assertSeeInOrder([
                route('admin.attendance.show', $newerRecord->id),
                route('admin.attendance.show', $olderRecord->id),
            ], false);
    }

    public function test_super_admin_attendance_records_are_listed_last_in_first_out(): void
    {
        $office = $this->createOfficeLocation([
            'name' => 'Super Admin Attendance Office',
        ]);
        $superAdmin = $this->createEmployee([
            'name' => 'Super Admin Attendance Viewer',
        ], $office);
        $olderUser = $this->createEmployee([
            'name' => 'Older Super Attendance User',
        ], $office);
        $newerUser = $this->createEmployee([
            'name' => 'Newer Super Attendance User',
        ], $office);

        $this->assignRole($superAdmin, Roles::SuperAdmin->value);
        $this->assignRole($olderUser, Roles::Employee->value);
        $this->assignRole($newerUser, Roles::Employee->value);

        $baseDate = Carbon::now('Asia/Jakarta')->startOfMonth();

        $olderRecord = $this->createAttendance($olderUser, $office, null, [
            'work_date' => $baseDate->copy()->addDays(12)->toDateString(),
            'created_at' => $baseDate->copy()->addDay()->setTime(7, 30),
            'updated_at' => $baseDate->copy()->addDay()->setTime(7, 30),
        ]);
        $newerRecord = $this->createAttendance($newerUser, $office, null, [
            'work_date' => $baseDate->copy()->addDays(1)->toDateString(),
            'created_at' => $baseDate->copy()->addDays(4)->setTime(10, 15),
            'updated_at' => $baseDate->copy()->addDays(4)->setTime(10, 15),
        ]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->get(route('super-admin.attendance.records'));

        $response->assertOk()
            ->assertSeeInOrder([
                route('super-admin.attendance.show', $newerRecord->id),
                route('super-admin.attendance.show', $olderRecord->id),
            ], false);
    }
}