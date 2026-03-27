<?php

namespace App\Http\Controllers\Api\Mobile\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceDailyStatusResolverService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceDailyStatusResolverService $attendanceDailyStatusResolverService,
    ) {}

    public function todayStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $date = isset($validated['date'])
            ? Carbon::createFromFormat('Y-m-d', $validated['date'], 'Asia/Jakarta')
            : now('Asia/Jakarta');

        $statusData = $this->attendanceDailyStatusResolverService->resolveForUser($user, $date);

        return response()->json([
            'message' => 'Attendance status for the selected date was retrieved successfully.',
            'data' => [
                'date' => $statusData->date->toDateString(),
                'status' => $statusData->status,
                'label' => $statusData->label,
                'attendance_id' => $statusData->attendanceId,
                'check_in_at' => $statusData->checkInAt?->toDateTimeString(),
                'check_out_at' => $statusData->checkOutAt?->toDateTimeString(),
                'is_late' => $statusData->isLate,
                'is_early_leave' => $statusData->isEarlyLeave,
                'is_suspicious' => $statusData->isSuspicious,
                'reason' => $statusData->reason,
            ],
        ], 200);
    }
}
