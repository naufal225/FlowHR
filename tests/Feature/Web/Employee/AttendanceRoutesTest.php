<?php

namespace Tests\Feature\Web\Employee;

use App\Enums\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceRoutesTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_employee_attendance_page(): void
    {
        $this->get(route('employee.attendance.index'))
            ->assertRedirect(route('login'));
    }

    public function test_employee_attendance_page_should_load_for_a_valid_employee_session(): void
    {
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);
        $this->assignRole($employee, Roles::Employee->value);

        $response = $this->actingAs($employee)
            ->withSession([
                'active_role' => Roles::Employee->value,
            ])
            ->get(route('employee.attendance.index'));

        $response->assertOk();
    }

    public function test_employee_attendance_history_page_resets_loading_state_safely(): void
    {
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);
        $this->assignRole($employee, Roles::Employee->value);

        $response = $this->actingAs($employee)
            ->withSession([
                'active_role' => Roles::Employee->value,
            ])
            ->get(route('employee.attendance.history'));

        $response->assertOk()
            ->assertDontSee('Loading attendance history', false)
            ->assertDontSee('@submit="loading = $event.target.reportValidity()"', false)
            ->assertDontSee('x-bind:disabled="loading"', false);
    }

    public function test_employee_attendance_detail_page_resets_loading_state_safely(): void
    {
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);
        $this->assignRole($employee, Roles::Employee->value);
        $attendance = $this->createAttendance($employee, $office);

        $response = $this->actingAs($employee)
            ->withSession([
                'active_role' => Roles::Employee->value,
            ])
            ->get(route('employee.attendance.show', $attendance->id));

        $response->assertOk()
            ->assertDontSee('Submitting correction request', false)
            ->assertDontSee('@pageshow.window="loading = false"', false)
            ->assertDontSee('@submit="loading = $event.target.reportValidity()"', false)
            ->assertDontSee('x-bind:disabled="loading"', false);
    }
}
