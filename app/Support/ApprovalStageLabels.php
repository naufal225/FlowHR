<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Roles;

final class ApprovalStageLabels
{
    public static function actor(Roles $role): string
    {
        return $role->label();
    }

    public static function status(Roles $role): string
    {
        return self::actor($role) . ' Status';
    }
}
