<?php

namespace App\Http\Controllers\Api\Mobile\Attendance;

use App\Data\Attendance\CheckOutData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\Attendance\CheckOutRequest;
use App\Services\Attendance\AttendanceCheckOutService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CheckOutController extends Controller
{
    public function __construct(
        protected AttendanceCheckOutService $attendanceCheckOutService
    ) {}

    public function __invoke(CheckOutRequest $request): JsonResponse
    {
        $data = CheckOutData::fromRequest($request);

        $attendance = $this->attendanceCheckOutService->checkOut($data);

        return response()->json([
            'success' => true,
            'message' => 'Check-out berhasil direkam.',
            'data' => [
                'id' => $attendance->id,
                'work_date' => Carbon::parse($attendance->work_date)->toDateString(),
                'check_in_at' => optional($attendance->check_in_at)?->toDateTimeString(),
                'check_out_at' => optional($attendance->check_out_at)?->toDateTimeString(),
                'check_out_status' => $attendance->check_out_status?->value ?? $attendance->check_out_status,
                'early_leave_minutes' => (int) ($attendance->early_leave_minutes ?? 0),
                'overtime_minutes' => (int) ($attendance->overtime_minutes ?? 0),
                'record_status' => $attendance->record_status?->value ?? $attendance->record_status,
                'is_suspicious' => (bool) $attendance->is_suspicious,
                'suspicious_reason' => $attendance->suspicious_reason,
            ],
        ], 200);
    }
}
