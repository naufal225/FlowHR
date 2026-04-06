<?php

namespace Tests\Feature\Web\Admin;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class DashboardAttendanceStateCardTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    #[DataProvider('dashboardRouteProvider')]
    public function test_dashboard_renders_attendance_state_card_for_each_role(
        string $routeName,
        string $activeRole,
    ): void {
        $office = $this->createOfficeLocation();
        $this->createAttendanceSetting($office);

        $employee = $this->createEmployee([], $office);
        $this->assignRole($employee, Roles::Employee->value);

        $dashboardUser = $this->createDashboardUser($activeRole, $office);

        $response = $this->actingAs($dashboardUser)
            ->withSession(['active_role' => $activeRole])
            ->get(route($routeName));

        $response->assertOk()
            ->assertSee('State Absensi Sekarang', false)
            ->assertSee('Check In', false)
            ->assertSee('Check Out', false)
            ->assertViewHas('dashboardAttendanceState', function (mixed $state): bool {
                if (! is_array($state)) {
                    return false;
                }

                return isset(
                    $state['badge']['label'],
                    $state['description'],
                    $state['check_in'],
                    $state['check_out']
                );
            });
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function dashboardRouteProvider(): array
    {
        return [
            'admin' => ['admin.dashboard', Roles::Admin->value],
            'super_admin' => ['super-admin.dashboard', Roles::SuperAdmin->value],
            'manager' => ['manager.dashboard', Roles::Manager->value],
            'approver' => ['approver.dashboard', Roles::Approver->value],
            'employee' => ['employee.dashboard', Roles::Employee->value],
            'finance' => ['finance.dashboard', Roles::Finance->value],
        ];
    }

    private function createDashboardUser(string $role, mixed $office): User
    {
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, $role);

        if (in_array($role, [Roles::Manager->value, Roles::Approver->value], true)) {
            $user->division()->update(['leader_id' => $user->id]);
        }

        return $user;
    }
}
