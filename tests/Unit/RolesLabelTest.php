<?php

namespace Tests\Unit;

use App\Enums\Roles;
use PHPUnit\Framework\TestCase;

class RolesLabelTest extends TestCase
{
    public function test_it_uses_business_labels_for_operational_roles(): void
    {
        $this->assertSame('Team Leader', Roles::Approver->label());
        $this->assertSame('Manager', Roles::Manager->label());
        $this->assertSame('Finance', Roles::Finance->label());
    }

    public function test_it_exposes_role_labels_as_a_consistent_map(): void
    {
        $this->assertSame([
            Roles::SuperAdmin->value => 'Super Admin',
            Roles::Admin->value => 'Admin',
            Roles::Approver->value => 'Team Leader',
            Roles::Employee->value => 'Employee',
            Roles::Manager->value => 'Manager',
            Roles::Finance->value => 'Finance',
        ], Roles::labels());
    }

    public function test_it_sorts_roles_by_weight_for_choose_role_ui(): void
    {
        $this->assertSame([
            Roles::SuperAdmin,
            Roles::Admin,
            Roles::Manager,
            Roles::Approver,
            Roles::Finance,
            Roles::Employee,
        ], Roles::sorted());

        $this->assertSame([
            Roles::SuperAdmin->value,
            Roles::Admin->value,
            Roles::Manager->value,
            Roles::Approver->value,
            Roles::Finance->value,
            Roles::Employee->value,
        ], Roles::selectionOrder());
    }
}
