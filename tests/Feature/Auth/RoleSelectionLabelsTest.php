<?php

namespace Tests\Feature\Auth;

use App\Enums\Roles;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSelectionLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_choose_role_page_uses_business_labels_instead_of_generic_approver_levels(): void
    {
        $user = User::factory()->create();

        $roleIds = collect([
            Roles::Approver->value,
            Roles::Manager->value,
            Roles::Finance->value,
        ])->map(fn (string $roleName) => Role::query()->firstOrCreate(['name' => $roleName])->id);

        $user->roles()->sync($roleIds->all());

        $response = $this->actingAs($user)->get(route('choose-role'));

        $response->assertOk()
            ->assertSee('Team Leader', false)
            ->assertSee('Manager', false)
            ->assertSee('Finance', false)
            ->assertDontSee('Approver 1', false)
            ->assertDontSee('Approver 2', false)
            ->assertDontSee('Approver 3', false);
    }
}
