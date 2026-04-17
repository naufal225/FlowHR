<?php

namespace Tests\Feature\Web\Admin;

use App\Enums\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class UserManagementAuthorizationTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_admin_cannot_delete_manager_user(): void
    {
        $admin = $this->createEmployee([
            'email' => 'admin.security@gmail.com',
        ]);
        $this->assignRole($admin, Roles::Admin->value);

        $manager = $this->createEmployee([
            'email' => 'manager.security@gmail.com',
        ]);
        $this->assignRole($manager, Roles::Manager->value);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->delete(route('admin.users.destroy', $manager));

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
        ]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->createEmployee([
            'email' => 'admin.self@gmail.com',
        ]);
        $this->assignRole($admin, Roles::Admin->value);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->delete(route('admin.users.destroy', $admin));

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_assign_super_admin_role_via_manual_request(): void
    {
        $admin = $this->createEmployee([
            'email' => 'admin.assign@gmail.com',
        ]);
        $this->assignRole($admin, Roles::Admin->value);

        $employee = $this->createEmployee([
            'email' => 'employee.assign@gmail.com',
        ]);
        $this->assignRole($employee, Roles::Employee->value);

        $response = $this->from(route('admin.users.edit', $employee))
            ->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->put(route('admin.users.update', $employee), [
                'name' => $employee->name,
                'email' => $employee->email,
                'roles' => [Roles::SuperAdmin->value],
                'division_id' => $employee->division_id,
                'office_location_id' => $employee->office_location_id,
            ]);

        $response->assertRedirect(route('admin.users.edit', $employee))
            ->assertSessionHasErrors(['roles']);
    }

    public function test_admin_index_can_filter_by_division_and_role_while_super_admins_remain_hidden(): void
    {
        $office = $this->createOfficeLocation();
        $divisionOps = $this->createDivision(['name' => 'Operations Division']);
        $divisionFinance = $this->createDivision(['name' => 'Finance Division']);

        $admin = $this->createEmployee([
            'name' => 'Admin List Actor',
            'email' => 'admin.list.actor@gmail.com',
        ], $office, $divisionOps);
        $this->assignRole($admin, Roles::Admin->value);

        $matchingEmployee = $this->createEmployee([
            'name' => 'Rani Ops Employee',
            'email' => 'rani.ops.employee@gmail.com',
        ], $office, $divisionOps);
        $this->assignRole($matchingEmployee, Roles::Employee->value);

        $sameDivisionDifferentRole = $this->createEmployee([
            'name' => 'Manager Ops User',
            'email' => 'manager.ops.user@gmail.com',
        ], $office, $divisionOps);
        $this->assignRole($sameDivisionDifferentRole, Roles::Manager->value);

        $differentDivisionEmployee = $this->createEmployee([
            'name' => 'Rani Finance Employee',
            'email' => 'rani.finance.employee@gmail.com',
        ], $office, $divisionFinance);
        $this->assignRole($differentDivisionEmployee, Roles::Employee->value);

        $hiddenSuperAdmin = $this->createEmployee([
            'name' => 'Hidden Super Admin',
            'email' => 'hidden.super.admin@gmail.com',
        ], $office, $divisionOps);
        $this->assignRole($hiddenSuperAdmin, Roles::SuperAdmin->value);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->get(route('admin.users.index', [
                'division_id' => $divisionOps->id,
                'role' => Roles::Employee->value,
            ]));

        $response->assertOk()
            ->assertSee('Rani Ops Employee', false)
            ->assertDontSee('Manager Ops User', false)
            ->assertDontSee('Rani Finance Employee', false)
            ->assertDontSee('Hidden Super Admin', false);

        $forcedRoleResponse = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->get(route('admin.users.index', [
                'role' => Roles::SuperAdmin->value,
            ]));

        $forcedRoleResponse->assertOk()
            ->assertDontSee('Hidden Super Admin', false);
    }

    public function test_super_admin_index_can_filter_super_admins_by_division_and_role(): void
    {
        $office = $this->createOfficeLocation();
        $divisionEngineering = $this->createDivision(['name' => 'Engineering Division']);
        $divisionPeopleOps = $this->createDivision(['name' => 'People Ops Division']);

        $superAdminActor = $this->createEmployee([
            'name' => 'Super Actor',
            'email' => 'super.actor.list@gmail.com',
        ], $office, $divisionPeopleOps);
        $this->assignRole($superAdminActor, Roles::SuperAdmin->value);

        $targetSuperAdmin = $this->createEmployee([
            'name' => 'Target Super Admin',
            'email' => 'target.super.admin@gmail.com',
        ], $office, $divisionEngineering);
        $this->assignRole($targetSuperAdmin, Roles::SuperAdmin->value);

        $otherSuperAdmin = $this->createEmployee([
            'name' => 'Other Super Admin',
            'email' => 'other.super.admin@gmail.com',
        ], $office, $divisionPeopleOps);
        $this->assignRole($otherSuperAdmin, Roles::SuperAdmin->value);

        $adminInSameDivision = $this->createEmployee([
            'name' => 'Admin In Engineering',
            'email' => 'admin.engineering@gmail.com',
        ], $office, $divisionEngineering);
        $this->assignRole($adminInSameDivision, Roles::Admin->value);

        $response = $this->actingAs($superAdminActor)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->get(route('super-admin.users.index', [
                'division_id' => $divisionEngineering->id,
                'role' => Roles::SuperAdmin->value,
            ]));

        $response->assertOk()
            ->assertSee('Target Super Admin', false)
            ->assertDontSee('Other Super Admin', false)
            ->assertDontSee('Admin In Engineering', false);
    }

    public function test_super_admin_can_view_and_open_edit_form_for_super_admin_and_admin_users(): void
    {
        $superAdmin = $this->createEmployee([
            'email' => 'super.actor@gmail.com',
        ]);
        $this->assignRole($superAdmin, Roles::SuperAdmin->value);

        $otherSuperAdmin = $this->createEmployee([
            'email' => 'super.target@gmail.com',
        ]);
        $this->assignRole($otherSuperAdmin, Roles::SuperAdmin->value);

        $admin = $this->createEmployee([
            'email' => 'admin.target@gmail.com',
        ]);
        $this->assignRole($admin, Roles::Admin->value);

        $this->actingAs($superAdmin)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->get(route('super-admin.users.show', $otherSuperAdmin))
            ->assertOk();

        $this->actingAs($superAdmin)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->get(route('super-admin.users.edit', $otherSuperAdmin))
            ->assertOk();

        $this->actingAs($superAdmin)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->get(route('super-admin.users.show', $admin))
            ->assertOk();

        $this->actingAs($superAdmin)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->get(route('super-admin.users.edit', $admin))
            ->assertOk();
    }

    public function test_super_admin_can_update_super_admin_user(): void
    {
        $superAdmin = $this->createEmployee([
            'email' => 'super.editor@gmail.com',
        ]);
        $this->assignRole($superAdmin, Roles::SuperAdmin->value);

        $target = $this->createEmployee([
            'name' => 'Target Super Admin',
            'email' => 'super.before@gmail.com',
        ]);
        $this->assignRole($target, Roles::SuperAdmin->value);

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->put(route('super-admin.users.update', $target), [
                'name' => 'Updated Super Admin',
                'email' => 'super.after@gmail.com',
                'roles' => [Roles::SuperAdmin->value],
                'division_id' => null,
                'office_location_id' => null,
            ]);

        $response->assertRedirect(route('super-admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Updated Super Admin',
            'email' => 'super.after@gmail.com',
        ]);

        $this->assertTrue($target->fresh()->userHasRole(Roles::SuperAdmin->value));
    }

    public function test_super_admin_can_delete_admin_user(): void
    {
        $superAdmin = $this->createEmployee([
            'email' => 'super.deleter@gmail.com',
        ]);
        $this->assignRole($superAdmin, Roles::SuperAdmin->value);

        $admin = $this->createEmployee([
            'email' => 'admin.deletable@gmail.com',
        ]);
        $this->assignRole($admin, Roles::Admin->value);

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->delete(route('super-admin.users.destroy', $admin));

        $response->assertRedirect(route('super-admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_super_admin_cannot_delete_their_own_account(): void
    {
        $superAdmin = $this->createEmployee([
            'email' => 'super.self@gmail.com',
        ]);
        $this->assignRole($superAdmin, Roles::SuperAdmin->value);

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_role' => Roles::SuperAdmin->value])
            ->delete(route('super-admin.users.destroy', $superAdmin));

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $superAdmin->id,
        ]);
    }
}
