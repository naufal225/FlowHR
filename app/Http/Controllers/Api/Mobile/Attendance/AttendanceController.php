<?php

namespace App\Http\Controllers\Api\Mobile\Attendance;

use App\Data\Attendance\AttendanceHistoryFilterData;
use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceDailyStatusResolverService;
use App\Services\Attendance\AttendanceHistoryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceDailyStatusResolverService $attendanceDailyStatusResolverService,
        private AttendanceHistoryService $attendanceHistoryService,
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

    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'record_status' => ['nullable', 'string'],
            'check_in_status' => ['nullable', 'string'],
            'check_out_status' => ['nullable', 'string'],
            'is_suspicious' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
        ]);

        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Convert request → DTO
        $filter = AttendanceHistoryFilterData::fromArray($validated);

        $paginator = $this->attendanceHistoryService->getEmployeeHistory(
            userId: $user->id,
            filter: $filter,
        );

        return response()->json([
            'message' => 'Attendance history retrieved successfully.',
            'data' => $this->transformHistoryCollection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function transformHistoryCollection(array $attendances): array
    {
        return array_map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'work_date' => $attendance->work_date?->toDateString(),
                'check_in_at' => $attendance->check_in_at?->toDateTimeString(),
                'check_out_at' => $attendance->check_out_at?->toDateTimeString(),
                'check_in_status' => $attendance->check_in_status?->value ?? null,
                'check_out_status' => $attendance->check_out_status?->value ?? null,
                'record_status' => $attendance->record_status?->value ?? null,
                'late_minutes' => $attendance->late_minutes,
                'early_leave_minutes' => $attendance->early_leave_minutes,
                'overtime_minutes' => $attendance->overtime_minutes,
                'is_suspicious' => $attendance->is_suspicious,
                'office_location' => [
                    'id' => $attendance->officeLocation?->id,
                    'name' => $attendance->officeLocation?->name,
                ],
            ];
        }, $attendances);
    }
}
