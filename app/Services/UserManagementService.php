<?php

namespace App\Services;

use App\Enums\Roles;
use App\Mail\ResetPasswordMail;
use App\Models\Division;
use App\Models\OfficeLocation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function getPaginatedUsers(
        User $actor,
        ?string $search,
        ?int $divisionId = null,
        ?string $role = null,
        int $perPage = 10
    ): LengthAwarePaginator
    {
        $selectedRole = $this->resolveFilterableRole($actor, $role);

        $users = User::query()
            ->with(['roles:id,name', 'division:id,name', 'officeLocation:id,name'])
            ->when($search, fn($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->when($divisionId !== null, fn($query) => $query->where('division_id', $divisionId))
            ->when($selectedRole !== null, function ($query) use ($selectedRole) {
                $query->whereHas('roles', function ($roleQuery) use ($selectedRole) {
                    $roleQuery->where('name', $selectedRole);
                });
            })
            ->when($this->shouldHideSuperAdminUsers($actor), function ($query) {
                $query->whereDoesntHave('roles', function ($roleQuery) {
                    $roleQuery->where('name', Roles::SuperAdmin->value);
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $roleLabels = $this->getRoleLabels();

        $users->getCollection()->transform(function (User $user) use ($roleLabels) {
            $user->role_display = $this->formatRoleDisplay($user, $roleLabels);

            return $user;
        });

        return $users;
    }

    public function getAssignableRoles(User $actor): Collection
    {
        $roles = collect(Roles::cases());

        if (! $this->isActingAsSuperAdmin($actor)) {
            $roles = $roles->filter(fn(Roles $role) => $role !== Roles::SuperAdmin);
        }

        return $roles
            ->sortBy(fn(Roles $role) => $role !== Roles::Employee)
            ->values();
    }

    public function getDivisions(): Collection
    {
        return Division::query()
            ->latest()
            ->get();
    }

    public function getOfficeLocations(): Collection
    {
        return OfficeLocation::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    public function getRoleLabels(): array
    {
        return Roles::labels();
    }

    public function formatRoleDisplay(User $user, ?array $roleLabels = null): string
    {
        $labels = $roleLabels ?? $this->getRoleLabels();

        return $user->roles
            ->pluck('name')
            ->map(fn(string $name) => $labels[$name] ?? Str::headline($name))
            ->join(', ');
    }

    public function createUser(User $actor, array $validated): User
    {
        $this->ensureRolesAreAssignable($actor, $validated['roles']);
        $this->ensureDivisionForApproverRoles($validated);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt('password'),
            'division_id' => $validated['division_id'] ?? null,
            'office_location_id' => $validated['office_location_id'] ?? null,
        ]);

        $this->syncUserRoles($user, $validated['roles'], false);
        $this->assignDivisionLeaderIfNeeded(
            user: $user,
            selectedRoles: $validated['roles'],
            divisionId: $validated['division_id'] ?? null
        );
        $this->queueResetPasswordEmail($user);

        return $user;
    }

    public function updateUser(User $actor, User $user, array $validated): User
    {
        $this->ensureRolesAreAssignable($actor, $validated['roles']);
        $this->ensureDivisionForApproverRoles($validated);

        if ($user->relationLoaded('division')) {
            $user->unsetRelation('division');
        }

        $currentDivision = $user->division;
        $newDivisionId = $validated['division_id'] ?? null;

        if (
            $currentDivision &&
            (int) $currentDivision->leader_id === (int) $user->id &&
            (
                (int) $newDivisionId !== (int) $user->division_id ||
                ! $this->hasApproverRole($validated['roles'])
            )
        ) {
            $currentDivision->update(['leader_id' => null]);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'division_id' => $newDivisionId,
            'office_location_id' => $validated['office_location_id'] ?? null,
        ]);

        $this->syncUserRoles($user, $validated['roles'], true);
        $this->assignDivisionLeaderIfNeeded(
            user: $user,
            selectedRoles: $validated['roles'],
            divisionId: $newDivisionId
        );

        return $user->refresh();
    }

    public function deleteUser(User $actor, User $user): void
    {
        Gate::forUser($actor)->authorize('delete', $user);

        if ($user->division && (int) $user->division->leader_id === (int) $user->id) {
            $user->division->update(['leader_id' => null]);
        }

        $user->delete();
    }

    public function shouldLogoutAfterRoleUpdate(User $user, array $updatedRoles, ?string $activeRole): bool
    {
        if (Auth::id() !== $user->id || empty($activeRole)) {
            return false;
        }

        return ! in_array($activeRole, $updatedRoles, true);
    }

    private function ensureDivisionForApproverRoles(array $validated): void
    {
        if (! $this->hasApproverRole($validated['roles'])) {
            return;
        }

        if (empty($validated['division_id'])) {
            throw ValidationException::withMessages([
                'division_id' => 'Division is required when assigning Team Leader or Manager role.',
            ]);
        }

        $divisionExists = Division::query()->whereKey($validated['division_id'])->exists();

        if (! $divisionExists) {
            throw ValidationException::withMessages([
                'division_id' => 'Division not found.',
            ]);
        }
    }

    private function ensureRolesAreAssignable(User $actor, array $roles): void
    {
        $allowedRoleValues = $this->getAssignableRoles($actor)
            ->map(fn(Roles $role) => $role->value)
            ->all();

        $invalidRoles = array_values(array_diff($roles, $allowedRoleValues));

        if ($invalidRoles === []) {
            return;
        }

        throw ValidationException::withMessages([
            'roles' => 'Selected roles are not permitted for your access level.',
        ]);
    }

    private function hasApproverRole(array $roles): bool
    {
        return ! empty(array_intersect($roles, [Roles::Approver->value, Roles::Manager->value]));
    }

    private function shouldHideSuperAdminUsers(User $actor): bool
    {
        return ! $this->isActingAsSuperAdmin($actor);
    }

    private function isActingAsSuperAdmin(User $actor): bool
    {
        return $this->resolveActorRole($actor) === Roles::SuperAdmin->value;
    }

    private function resolveActorRole(User $actor): ?string
    {
        $activeRole = $actor->getActiveRole();

        if (is_string($activeRole) && $actor->userHasRole($activeRole)) {
            return $activeRole;
        }

        foreach ([Roles::SuperAdmin->value, Roles::Admin->value] as $role) {
            if ($actor->userHasRole($role)) {
                return $role;
            }
        }

        return null;
    }

    private function resolveFilterableRole(User $actor, ?string $role): ?string
    {
        if (! is_string($role) || trim($role) === '') {
            return null;
        }

        $normalizedRole = trim($role);
        $allowedRoles = $this->getAssignableRoles($actor)
            ->map(fn(Roles $roleItem) => $roleItem->value)
            ->all();

        if (! in_array($normalizedRole, $allowedRoles, true)) {
            return null;
        }

        return $normalizedRole;
    }

    private function syncUserRoles(User $user, array $roleNames, bool $isUpdate): void
    {
        $roleIds = Role::query()
            ->whereIn('name', $roleNames)
            ->pluck('id');

        if ($isUpdate) {
            $user->roles()->sync($roleIds);

            return;
        }

        $user->roles()->attach($roleIds);
    }

    private function assignDivisionLeaderIfNeeded(User $user, array $selectedRoles, ?int $divisionId): void
    {
        if (! $this->hasApproverRole($selectedRoles) || empty($divisionId)) {
            return;
        }

        $division = Division::query()->find($divisionId);

        if (! $division) {
            return;
        }

        if ($division->leader_id && (int) $division->leader_id !== (int) $user->id) {
            $oldLeader = $division->leader;

            if ($oldLeader) {
                $approverRoleIds = Role::query()
                    ->whereIn('name', [Roles::Approver->value, Roles::Manager->value])
                    ->pluck('id');

                $oldLeader->roles()->detach($approverRoleIds);
            }
        }

        $division->update(['leader_id' => $user->id]);
    }

    private function queueResetPasswordEmail(User $user): void
    {
        $token = Password::createToken($user);
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

        Mail::to($user->email)->queue(new ResetPasswordMail($user->name, $resetUrl));
    }
}
