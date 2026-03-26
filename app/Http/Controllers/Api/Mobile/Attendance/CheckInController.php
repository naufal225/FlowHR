<?php

namespace App\Http\Controllers\Api\Mobile\Attendance;

use App\Http\Controllers\Controller;
use App\Data\Attendance\CheckInData;
use App\Http\Requests\Mobile\Attendance\CheckInRequest;
use App\Services\Attendance\AttendanceCheckInService;
use Illuminate\Http\JsonResponse;


class CheckInController extends Controller
{
    public function __construct(
        protected AttendanceCheckInService $attendanceCheckInService
    ) {}

    public function __invoke(CheckInRequest $request): JsonResponse
    {
        $data = CheckInData::fromRequest($request);
        $attendance = $this->attendanceCheckInService->checkIn($data);

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil direkam.',
            'data' => [
                'id' => $attendance->id,
                'work_date' => \Carbon\Carbon::parse($attendance->work_date)->toDateString(),
                'check_in_at' => optional($attendance->check_in_at)?->toDateTimeString(),
                'check_in_status' => $attendance->check_in_status?->value ?? $attendance->check_in_status,
                'late_minutes' => (int) ($attendance->late_minutes ?? 0),
                'record_status' => $attendance->record_status?->value ?? $attendance->record_status,
                'is_suspicious' => (bool) $attendance->is_suspicious,
                'suspicious_reason' => $attendance->suspicious_reason,
            ],
        ], 200);
    }
}
