<?php

namespace Tests\Feature\Api\Mobile\Auth;

use App\Enums\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class MobileAuthControllerTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_employee_can_login_and_receive_mobile_token(): void
    {
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([
            'email' => 'employee@example.com',
            'password' => bcrypt('password123'),
        ], $office);
        $this->assignRole($employee, Roles::Employee->value);

        $response = $this->postJson('/api/mobile/auth/login', [
            'email' => 'employee@example.com',
            'password' => 'password123',
            'device_name' => 'android-test',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Login mobile berhasil.',
                'data' => [
                    'token_type' => 'Bearer',
                    'user' => [
                        'id' => $employee->id,
                        'email' => 'employee@example.com',
                        'mobile_scope' => Roles::Employee->value,
                    ],
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_non_employee_role_is_rejected_from_mobile_login(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ], $office);
        $this->assignRole($user, Roles::Admin->value);

        $response = $this->postJson('/api/mobile/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => 'MOBILE_AUTH_NOT_ALLOWED',
            ]);
    }

    public function test_authenticated_mobile_employee_can_fetch_profile_and_logout_current_token(): void
    {
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([
            'email' => 'me@example.com',
        ], $office);
        $this->assignRole($employee, Roles::Employee->value);

        $token = $employee->createToken('ios-test')->plainTextToken;

        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/mobile/auth/me');

        $meResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $employee->id,
                    'email' => 'me@example.com',
                    'mobile_scope' => Roles::Employee->value,
                ],
            ]);

        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/mobile/auth/logout');

        $logoutResponse->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logout mobile berhasil.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertNull(PersonalAccessToken::findToken($token));
    }
}
