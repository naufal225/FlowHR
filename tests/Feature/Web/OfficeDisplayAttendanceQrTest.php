<?php

namespace Tests\Feature\Web;

use App\Models\AttendanceQrDisplaySession;
use App\Models\AttendanceQrToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class OfficeDisplayAttendanceQrTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_signed_display_url_can_render_and_return_qr_status_payload(): void
    {
        $office = $this->createOfficeLocation();
        $this->createAttendanceSetting($office, [
            'is_active' => true,
            'qr_rotation_seconds' => 30,
        ]);
        $creator = $this->createEmployee([], $office);

        $rawKey = Str::random(64);
        $session = AttendanceQrDisplaySession::query()->create([
            'office_location_id' => $office->id,
            'name' => 'Lobby TV',
            'token_hash' => hash('sha256', $rawKey),
            'expires_at' => now()->addDay(),
            'created_by' => $creator->id,
        ]);

        $expiresAt = now()->addMinutes(30);
        $displayUrl = URL::temporarySignedRoute('office-display.attendance.qr.show', $expiresAt, [
            'session' => $session->id,
            'key' => $rawKey,
        ]);
        $statusUrl = URL::temporarySignedRoute('office-display.attendance.qr.status', $expiresAt, [
            'session' => $session->id,
            'key' => $rawKey,
        ]);

        $this->get($displayUrl)
            ->assertOk()
            ->assertSee('Attendance QR Display', false)
            ->assertSee('Display #'.$session->id, false);

        $response = $this->getJson($statusUrl);

        $response->assertOk()
            ->assertJsonStructure([
                'office_name',
                'token',
                'expires_at_iso',
                'status_label',
                'server_time',
            ]);

        $token = (string) $response->json('token');
        $this->assertNotSame('', $token);
        $this->assertDatabaseHas('attendance_qr_tokens', [
            'office_location_id' => $office->id,
            'token' => $token,
            'is_active' => true,
        ]);
    }

    public function test_revoked_or_invalid_display_session_is_rejected(): void
    {
        $office = $this->createOfficeLocation();
        $this->createAttendanceSetting($office, ['is_active' => true]);
        $creator = $this->createEmployee([], $office);

        $keyA = Str::random(64);
        $sessionA = AttendanceQrDisplaySession::query()->create([
            'office_location_id' => $office->id,
            'name' => 'Session A',
            'token_hash' => hash('sha256', $keyA),
            'expires_at' => now()->addHour(),
            'created_by' => $creator->id,
            'revoked_at' => now(),
        ]);

        $revokedUrl = URL::temporarySignedRoute('office-display.attendance.qr.status', now()->addMinutes(10), [
            'session' => $sessionA->id,
            'key' => $keyA,
        ]);

        $this->getJson($revokedUrl)->assertStatus(410);

        $this->get('/office-display/attendance/qr/'.$sessionA->id.'/status?key='.$keyA)->assertForbidden();

        $keyB = Str::random(64);
        $sessionB = AttendanceQrDisplaySession::query()->create([
            'office_location_id' => $office->id,
            'name' => 'Session B',
            'token_hash' => hash('sha256', $keyB),
            'expires_at' => now()->addHour(),
            'created_by' => $creator->id,
        ]);

        $mismatchUrl = URL::temporarySignedRoute('office-display.attendance.qr.status', now()->addMinutes(10), [
            'session' => $sessionB->id,
            'key' => $keyA,
        ]);

        $this->getJson($mismatchUrl)->assertForbidden();
    }

    public function test_status_refresh_rotates_expired_qr_once_and_keeps_single_active_token(): void
    {
        $office = $this->createOfficeLocation();
        $this->createAttendanceSetting($office, [
            'is_active' => true,
            'qr_rotation_seconds' => 30,
        ]);
        $creator = $this->createEmployee([], $office);

        $this->createAttendanceQrToken($office, [
            'token' => 'EXPIREDTOKEN0000000000001',
            'generated_at' => now('Asia/Jakarta')->subMinutes(3),
            'expired_at' => now('Asia/Jakarta')->subSecond(),
            'is_active' => true,
        ]);

        $rawKey = Str::random(64);
        $session = AttendanceQrDisplaySession::query()->create([
            'office_location_id' => $office->id,
            'name' => 'Main TV',
            'token_hash' => hash('sha256', $rawKey),
            'expires_at' => now()->addDay(),
            'created_by' => $creator->id,
        ]);

        $statusUrl = URL::temporarySignedRoute('office-display.attendance.qr.status', now()->addMinutes(10), [
            'session' => $session->id,
            'key' => $rawKey,
        ]);

        $firstToken = (string) $this->getJson($statusUrl)
            ->assertOk()
            ->json('token');

        $secondToken = (string) $this->getJson($statusUrl)
            ->assertOk()
            ->json('token');

        $this->assertNotSame('', $firstToken);
        $this->assertSame($firstToken, $secondToken);
        $this->assertSame(1, AttendanceQrToken::query()
            ->where('office_location_id', $office->id)
            ->where('is_active', true)
            ->count());
    }
}
