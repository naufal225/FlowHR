<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceQrDisplaySession;
use App\Services\Attendance\AttendanceQrDisplaySessionService;
use App\Services\Attendance\AttendanceQrManagementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OfficeDisplayAttendanceQrController extends Controller
{
    public function __construct(
        private readonly AttendanceQrManagementService $attendanceQrManagementService,
        private readonly AttendanceQrDisplaySessionService $attendanceQrDisplaySessionService,
    ) {
    }

    public function show(Request $request, int $session): Response
    {
        $displaySession = $this->resolvedSession($request, $session);
        $payload = $this->displayPayload($displaySession);

        return response()
            ->view('office-display.attendance.qr', [
                'session' => $displaySession,
                'statusUrl' => $this->attendanceQrDisplaySessionService->makeStatusUrlFromRequest($displaySession, $request),
                'pollingIntervalMs' => max(1000, (int) config('attendance.qr_display.polling_interval_ms', 2500)),
                'qrPayload' => $payload,
            ])
            ->withHeaders($this->noStoreHeaders());
    }

    public function status(Request $request, int $session): JsonResponse
    {
        $displaySession = $this->resolvedSession($request, $session);

        return response()
            ->json($this->displayPayload($displaySession))
            ->withHeaders($this->noStoreHeaders());
    }

    private function resolvedSession(Request $request, int $sessionId): AttendanceQrDisplaySession
    {
        $session = $request->attributes->get('attendance_qr_display_session');

        if (!$session instanceof AttendanceQrDisplaySession || $session->id !== $sessionId) {
            abort(403, 'Display session is invalid.');
        }

        return $session;
    }

    private function displayPayload(AttendanceQrDisplaySession $session): array
    {
        $office = $session->officeLocation;
        $graceSeconds = max(0, (int) config('attendance.qr_display.regenerate_grace_seconds', 3));
        $token = $this->attendanceQrManagementService->ensureFreshForOffice($office, $graceSeconds);
        $statusLabel = 'Unavailable';

        if ($token !== null && $token->is_currently_valid) {
            $statusLabel = 'Active';
        } elseif ($token !== null) {
            $statusLabel = 'Inactive';
        }

        return [
            'office_name' => $office->name,
            'token' => $token?->token,
            'expires_at_iso' => $token?->expired_at?->toIso8601String(),
            'expires_at_formatted' => $session?->expires_at
                ? $session->expires_at
                    ->copy()
                    ->timezone($session->officeLocation->timezone)
                    ->locale('id')
                    ->translatedFormat('d F Y H:i')
                : '-',
            'status_label' => $statusLabel,
            'server_time' => now($office->timezone ?? config('app.timezone', 'Asia/Jakarta'))->toIso8601String(),
        ];
    }

    private function noStoreHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Fri, 01 Jan 1990 00:00:00 GMT',
        ];
    }
}
