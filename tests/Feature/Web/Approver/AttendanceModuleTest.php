<?php

namespace Tests\Feature\Web\Approver;

use App\Enums\Roles;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceModuleTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_approver_only_sees_and_reviews_subordinate_corrections(): void
    {
        $office = $this->createOfficeLocation();
        $this->createAttendanceSetting($office, [
            'work_start_time' => '09:00:00',
            'work_end_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
        ]);

        $leaderDivision = $this->createDivision();
        $approver = $this->createEmployee(['name' => 'Leader Approver'], $office, $leaderDivision);
        $this->assignRole($approver, Roles::Approver->value);
        $leaderDivision->update(['leader_id' => $approver->id]);

        $subordinate = $this->createEmployee(['name' => 'Subordinate Employee'], $office, $leaderDivision);
        $this->assignRole($subordinate, Roles::Employee->value);
        $subordinateAttendance = $this->createAttendance($subordinate, $office, null, [
            'work_date' => '2026-03-30',
        ]);
        $allowedCorrection = AttendanceCorrection::query()->create([
            'user_id' => $subordinate->id,
            'attendance_id' => $subordinateAttendance->id,
            'requested_check_in_time' => Carbon::parse('2026-03-30 09:00:00', 'Asia/Jakarta'),
            'reason' => 'Subordinate correction',
            'original_attendance_snapshot' => ['work_date' => '2026-03-30'],
            'status' => 'pending',
        ]);

        $otherDivision = $this->createDivision();
        $otherLeader = $this->createEmployee(['name' => 'Other Leader'], $office, $otherDivision);
        $this->assignRole($otherLeader, Roles::Approver->value);
        $otherDivision->update(['leader_id' => $otherLeader->id]);

        $foreignEmployee = $this->createEmployee(['name' => 'Foreign Employee'], $office, $otherDivision);
        $this->assignRole($foreignEmployee, Roles::Employee->value);
        $foreignAttendance = $this->createAttendance($foreignEmployee, $office, null, [
            'work_date' => '2026-03-29',
        ]);
        $foreignCorrection = AttendanceCorrection::query()->create([
            'user_id' => $foreignEmployee->id,
            'attendance_id' => $foreignAttendance->id,
            'requested_check_in_time' => Carbon::parse('2026-03-29 09:00:00', 'Asia/Jakarta'),
            'reason' => 'Foreign correction',
            'original_attendance_snapshot' => ['work_date' => '2026-03-29'],
            'status' => 'pending',
        ]);

        $indexResponse = $this->actingAs($approver)
            ->withSession(['active_role' => Roles::Approver->value])
            ->get(route('approver.attendance.corrections.index'));

        $indexResponse->assertOk()
            ->assertSee('Subordinate correction', false)
            ->assertDontSee('Foreign correction', false);

        $showForeignResponse = $this->actingAs($approver)
            ->withSession(['active_role' => Roles::Approver->value])
            ->get(route('approver.attendance.corrections.show', $foreignCorrection->id));

        $showForeignResponse->assertNotFound();

        $reviewResponse = $this->actingAs($approver)
            ->withSession(['active_role' => Roles::Approver->value])
            ->post(route('approver.attendance.corrections.review', $allowedCorrection->id), [
                'action' => 'approve',
                'reviewer_note' => 'Approved by team leader.',
            ]);

        $reviewResponse->assertRedirect(route('approver.attendance.corrections.show', $allowedCorrection->id));

        $allowedCorrection->refresh();

        $this->assertSame('approved', $allowedCorrection->status);
        $this->assertSame($approver->id, $allowedCorrection->reviewed_by);
        $this->assertSame('Approved by team leader.', $allowedCorrection->reviewer_note);
    }
}
