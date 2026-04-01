<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\AttendanceCorrectionRequest;
use App\Services\Attendance\AttendanceCorrectionQueryService;
use App\Services\Attendance\AttendanceCorrectionSubmissionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    public function __construct(
        private readonly AttendanceCorrectionSubmissionService $attendanceCorrectionSubmissionService,
        private readonly AttendanceCorrectionQueryService $attendanceCorrectionQueryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,all'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        abort_if($user === null, 401, 'Unauthenticated.');

        $paginator = $this->attendanceCorrectionQueryService->getEmployeeCorrections(
            userId: (int) $user->id,
            status: $validated['status'] ?? null,
            perPage: (int) ($validated['per_page'] ?? 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat koreksi absensi berhasil dimuat.',
            'data' => collect($paginator->items())
                ->map(fn ($correction) => $this->transformCorrectionSummary($correction))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(AttendanceCorrectionRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401, 'Unauthenticated.');

        $validated = $request->validated();

        $correction = $this->attendanceCorrectionSubmissionService->submit(
            user: $user,
            attendanceId: (int) $validated['attendance_id'],
            requestedCheckInTime: isset($validated['requested_check_in_time'])
                ? Carbon::parse((string) $validated['requested_check_in_time'], 'Asia/Jakarta')
                : null,
            requestedCheckOutTime: isset($validated['requested_check_out_time'])
                ? Carbon::parse((string) $validated['requested_check_out_time'], 'Asia/Jakarta')
                : null,
            reason: (string) $validated['reason'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan koreksi absensi berhasil dikirim.',
            'data' => $this->transformCorrectionSummary($correction),
        ], 201);
    }

    public function show(Request $request, int $correctionId): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401, 'Unauthenticated.');

        $correction = $this->attendanceCorrectionQueryService->getEmployeeCorrectionDetail((int) $user->id, $correctionId);

        return response()->json([
            'success' => true,
            'message' => 'Detail koreksi absensi berhasil dimuat.',
            'data' => [
                ...$this->transformCorrectionSummary($correction),
                'attendance' => [
                    'id' => $correction->attendance?->id,
                    'work_date' => $correction->attendance?->work_date?->toDateString(),
                    'check_in_at' => $correction->attendance?->check_in_at?->toDateTimeString(),
                    'check_out_at' => $correction->attendance?->check_out_at?->toDateTimeString(),
                    'office_location' => [
                        'id' => $correction->attendance?->officeLocation?->id,
                        'name' => $correction->attendance?->officeLocation?->name,
                    ],
                ],
                'review' => [
                    'reviewed_by' => $correction->reviewer?->id,
                    'reviewer_name' => $correction->reviewer?->name,
                    'reviewed_at' => $correction->reviewed_at?->toDateTimeString(),
                    'reviewer_note' => $correction->reviewer_note,
                    'applied_by' => $correction->appliedBy?->id,
                    'applied_by_name' => $correction->appliedBy?->name,
                    'applied_at' => $correction->applied_at?->toDateTimeString(),
                ],
                'original_attendance_snapshot' => $correction->original_attendance_snapshot,
                'resulting_attendance_snapshot' => $correction->resulting_attendance_snapshot,
            ],
        ]);
    }

    private function transformCorrectionSummary($correction): array
    {
        return [
            'id' => $correction->id,
            'attendance_id' => $correction->attendance_id,
            'status' => $correction->status,
            'reason' => $correction->reason,
            'requested_check_in_time' => $correction->requested_check_in_time?->toDateTimeString(),
            'requested_check_out_time' => $correction->requested_check_out_time?->toDateTimeString(),
            'reviewer_note' => $correction->reviewer_note,
            'created_at' => $correction->created_at?->toDateTimeString(),
            'updated_at' => $correction->updated_at?->toDateTimeString(),
            'reviewed_at' => $correction->reviewed_at?->toDateTimeString(),
            'attendance_summary' => [
                'work_date' => $correction->attendance?->work_date?->toDateString(),
                'office_location_name' => $correction->attendance?->officeLocation?->name,
            ],
        ];
    }
}
