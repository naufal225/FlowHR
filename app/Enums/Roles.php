<?php

namespace App\Enums;

enum Roles: string
{
    case SuperAdmin = "superAdmin";
    case Admin = "admin";
    case Approver = "approver"; // ini team lead
    case Employee = "employee";
    case Manager = "manager";
    case Finance = "finance";

    public static function values()
    {
        return array_map(fn($role) => $role->value, self::cases());
    }

    public static function labels(): array
    {
        return array_reduce(
            self::cases(),
            function (array $labels, self $role): array {
                $labels[$role->value] = $role->label();

                return $labels;
            },
            [],
        );
    }

    public function weight(): int
    {
        return match ($this) {
            self::SuperAdmin => 100,
            self::Admin => 90,
            self::Manager => 80,
            self::Approver => 70,
            self::Finance => 60,
            self::Employee => 10,
        };
    }

    public static function sorted(): array
    {
        return collect(self::cases())
            ->sortByDesc(fn (self $role) => $role->weight())
            ->values()
            ->all();
    }

    public static function selectionOrder(): array
    {
        return array_map(
            fn (self $role) => $role->value,
            self::sorted(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Finance => 'Finance',
            self::Manager => 'Manager',
            self::Approver => 'Team Leader',
            self::Employee => 'Employee',
        };
    }
}
