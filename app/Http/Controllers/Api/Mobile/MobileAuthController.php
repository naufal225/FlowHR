<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\Roles;
use App\Exceptions\Attendance\MobileAuthNotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        /** @var User|null $user */
        $user = User::query()
            ->with(['roles:id,name', 'division:id,name', 'officeLocation:id,name,address'])
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null || ! Hash::check((string) $credentials['password'], (string) $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password tidak valid.',
                'code' => 'INVALID_CREDENTIALS',
            ], 422);
        }

        $this->assertMobileEmployeeAccess($user);

        $token = $user->createToken((string) ($credentials['device_name'] ?? 'mobile-employee-app'));

        return response()->json([
            'success' => true,
            'message' => 'Login mobile berhasil.',
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'user' => $this->transformUser($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_if($user === null, 401, 'Unauthenticated.');
        $user->loadMissing(['roles:id,name', 'division:id,name', 'officeLocation:id,name,address']);

        return response()->json([
            'success' => true,
            'message' => 'Profil mobile berhasil dimuat.',
            'data' => $this->transformUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout mobile berhasil.',
            'data' => null,
        ]);
    }

    private function assertMobileEmployeeAccess(User $user): void
    {
        if (! $user->is_active || ! $user->hasRole(Roles::Employee->value)) {
            throw new MobileAuthNotAllowedException([
                'user_id' => $user->id,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name')->all(),
            ]);
        }
    }

    private function transformUser(User $user): array
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
        ];
    }
}
