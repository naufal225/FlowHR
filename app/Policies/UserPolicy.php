<?php

namespace App\Policies;

use App\Enums\Roles;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $this->isAdmin($actor) || $this->isSuperAdmin($actor);
    }

    public function view(User $actor, User $target): bool
    {
        if ($this->isSuperAdmin($actor)) {
            return true;
        }

        if ($this->isAdmin($actor)) {
            return ! $target->userHasRole(Roles::SuperAdmin->value);
        }

        return false;
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->view($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        if ((int) $actor->id === (int) $target->id) {
            return false;
        }

        if ($this->isSuperAdmin($actor)) {
            return ! $target->userHasRole(Roles::SuperAdmin->value);
        }

        if ($this->isAdmin($actor)) {
            return ! $target->userHasRole(Roles::SuperAdmin->value)
                && ! $target->userHasRole(Roles::Manager->value);
        }

        return false;
    }

    private function isAdmin(User $actor): bool
    {
        return $this->resolveActorRole($actor) === Roles::Admin->value;
    }

    private function isSuperAdmin(User $actor): bool
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
}
