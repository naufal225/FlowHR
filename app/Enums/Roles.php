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

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Finance => 'Approver 3',
            self::Manager => 'Approver 2',
            self::Approver => 'Approver 1',
            self::Employee => 'Employee',
        };
    }
}
