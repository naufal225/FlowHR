<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Support\Str;

class MobileUserTransformer
{
    public static function transform(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile_scope' => Roles::Employee->value,
            'division' => [
                'id' => $user->division?->id,
                'name' => $user->division?->name,
            ],
            'office_location' => [
                'id' => $user->officeLocation?->id,
                'name' => $user->officeLocation?->name,
                'address' => $user->officeLocation?->address,
            ],
            'roles' => $user->roles->pluck('name')->values()->all(),
            'url_profile' => self::resolveProfileUrl($user->url_profile),
        ];
    }

    private static function resolveProfileUrl(?string $urlProfile): ?string
    {
        if ($urlProfile === null || trim($urlProfile) === '') {
            return null;
        }

        if (Str::startsWith($urlProfile, ['http://', 'https://'])) {
            return $urlProfile;
        }

        return url($urlProfile);
    }
}
