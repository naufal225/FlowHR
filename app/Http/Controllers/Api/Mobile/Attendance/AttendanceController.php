<?php

namespace App\Http\Controllers\Api\Mobile\Attendance;

use App\Data\Attendance\AttendanceHistoryFilterData;
use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceDailyStatusResolverService;
use App\Services\Attendance\AttendanceDetailService;
use App\Services\Attendance\AttendanceHistoryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceDailyStatusResolverService $attendanceDailyStatusResolverService,
        private AttendanceHistoryService $attendanceHistoryService,
        private AttendanceDetailService $attendanceDetailService,
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

    public function detail(Request $request, int $attendanceId): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $attendance = $this->attendanceDetailService->getEmployeeAttendanceDetail(
            userId: (int) $user->id,
            attendanceId: $attendanceId,
        );

        return response()->json([
            'message' => 'Attendance detail retrieved successfully.',
            'data' => $this->transformAttendanceDetail($attendance),
        ], 200);
    }

    private function transformAttendanceDetail($attendance): array
    {
        return [
            'id' => $attendance->id,
            'work_date' => $attendance->work_date?->toDateString(),

            'record' => [
                'record_status' => $attendance->record_status?->value ?? null,
                'check_in_status' => $attendance->check_in_status?->value ?? null,
                'check_out_status' => $attendance->check_out_status?->value ?? null,
                'late_minutes' => $attendance->late_minutes,
                'early_leave_minutes' => $attendance->early_leave_minutes,
                'overtime_minutes' => $attendance->overtime_minutes,
                'is_suspicious' => (bool) $attendance->is_suspicious,
                'suspicious_reason' => $attendance->suspicious_reason,
                'notes' => $attendance->notes,
            ],

            'check_in' => [
                'at' => $attendance->check_in_at?->toDateTimeString(),
                'recorded_at' => $attendance->check_in_recorded_at?->toDateTimeString(),
                'latitude' => $attendance->check_in_latitude !== null ? (float) $attendance->check_in_latitude : null,
                'longitude' => $attendance->check_in_longitude !== null ? (float) $attendance->check_in_longitude : null,
                'accuracy_meter' => $attendance->check_in_accuracy_meter !== null ? (float) $attendance->check_in_accuracy_meter : null,
            ],

            'check_out' => [
                'at' => $attendance->check_out_at?->toDateTimeString(),
                'recorded_at' => $attendance->check_out_recorded_at?->toDateTimeString(),
                'latitude' => $attendance->check_out_latitude !== null ? (float) $attendance->check_out_latitude : null,
                'longitude' => $attendance->check_out_longitude !== null ? (float) $attendance->check_out_longitude : null,
                'accuracy_meter' => $attendance->check_out_accuracy_meter !== null ? (float) $attendance->check_out_accuracy_meter : null,
            ],

            'office_location' => [
                'id' => $attendance->officeLocation?->id,
                'name' => $attendance->officeLocation?->name,
                'address' => $attendance->officeLocation?->address,
                'latitude' => $attendance->officeLocation?->latitude !== null ? (float) $attendance->officeLocation->latitude : null,
                'longitude' => $attendance->officeLocation?->longitude !== null ? (float) $attendance->officeLocation->longitude : null,
                'radius_meter' => $attendance->officeLocation?->radius_meter,
            ],

            'qr_token_audit' => [
                'id' => $attendance->attendanceQrToken?->id,
                'generated_at' => $attendance->attendanceQrToken?->generated_at?->toDateTimeString(),
                'expired_at' => $attendance->attendanceQrToken?->expired_at?->toDateTimeString(),
                'is_active' => $attendance->attendanceQrToken?->is_active,
            ],

            'logs' => $attendance->logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action_type' => $log->action_type?->value ?? null,
                    'action_status' => $log->action_status?->value ?? null,
                    'latitude' => $log->latitude !== null ? (float) $log->latitude : null,
                    'longitude' => $log->longitude !== null ? (float) $log->longitude : null,
                    'accuracy_meter' => $log->accuracy_meter !== null ? (float) $log->accuracy_meter : null,
                    'ip_address' => $log->ip_address,
                    'device_info' => $log->device_info,
                    'message' => $log->message,
                    'occurred_at' => $log->occurred_at?->toDateTimeString(),
                ];
            })->values()->all(),

            'audit' => [
                'attendance_qr_token_id' => $attendance->attendance_qr_token_id,
                'office_location_id' => $attendance->office_location_id,
                'created_at' => $attendance->created_at?->toDateTimeString(),
                'updated_at' => $attendance->updated_at?->toDateTimeString(),
            ],
        ];
    }
}
