<?php

use Illuminate\Support\Facades\Broadcast;
use App\Enums\Roles;
use App\Models\User;

Broadcast::channel('approver.division.{divisionId}', function (User $user, $divisionId) {
    return $user
        && $user->hasActiveRole(Roles::Approver->value)
        && (int)$user->division_id === (int)$divisionId;
});

Broadcast::channel('manager.approval', function (User $user) {
    return $user
        && $user->hasActiveRole(Roles::Manager->value);
});

Broadcast::channel('send-message', function ($user) {
    return true;
});
