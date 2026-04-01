<?php

declare(strict_types=1);

namespace App\Http\Controllers\ApproverController;

use App\Data\Attendance\AttendanceHistoryFilterData;
use App\Exceptions\Attendance\AttendanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\ReviewAttendanceCorrectionRequest;
use App\Models\AttendanceCorrection;
use App\Services\Attendance\AttendanceCorrectionApprovalService;
use App\Services\Attendance\AttendanceCorrectionQueryService;
use App\Services\Attendance\AttendanceUiService;
use App\Services\Attendance\TeamAttendanceOverviewService;
use App\Services\Attendance\TeamAttendanceQueryService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly TeamAttendanceOverviewService $teamAttendanceOverviewService,
        private readonly TeamAttendanceQueryService $teamAttendanceQueryService,
        private readonly AttendanceUiService $attendanceUiService,
        private readonly AttendanceCorrectionQueryService $attendanceCorrectionQueryService,
        private readonly AttendanceCorrectionApprovalService $attendanceCorrectionApprovalService,
    ) {}

    public function index(Request $request): View
    {
        $leader = $request->user();
        abort_if($leader === null, 401);

        $officeLocations = $this->teamAttendanceQueryService->officeLocationsForLeader((int) $leader->id);
        $selectedOfficeId = $request->filled('office_location_id') ? (int) $request->input('office_location_id') : null;
        $selectedOffice = $selectedOfficeId !== null ? $officeLocations->firstWhere('id', $selectedOfficeId) : $officeLocations->first();
        $quickFilter = (string) $request->input('quick', 'all');
        $today = now('Asia/Jakarta')->startOfDay();

        $overview = $this->teamAttendanceOverviewService->build(
            leaderId: (int) $leader->id,
            officeLocationId: $selectedOffice?->id,
            date: $today,
            quickFilter: $quickFilter,
        );

        return $this->sharedView('attendance.admin.overview', [
            'headerTitle' => 'Team Attendance Overview',
            'headerSubtitle' => 'Monitor attendance operasional untuk anggota tim yang berada di bawah tanggung jawab Anda.',
            'officeLocations' => $officeLocations,
            'selectedOffice' => $selectedOffice,
            'todayLabel' => $today->translatedFormat('D, d M Y'),
            'quickFilter' => $quickFilter,
            'quickFilters' => [
                'all' => 'All',
                'checked_in' => 'Checked In',
                'late' => 'Late',
                'not_checked_in' => 'Not Checked In',
                'suspicious' => 'Suspicious',
            ],
            'stats' => $overview['stats'],
            'rows' => $overview['rows'],
            'priorityIssues' => $overview['priority_issues'],
        ]);
    }

    public function records(Request $request): View
    {
        $leader = $request->user();
        abort_if($leader === null, 401);

        $officeLocations = $this->teamAttendanceQueryService->officeLocationsForLeader((int) $leader->id);
        $employees = $this->teamAttendanceQueryService->subordinateEmployees((int) $leader->id);
        $filters = $this->historyFilterInput($request);
        $records = $this->teamAttendanceQueryService->getLeaderHistory(
            leaderId: (int) $leader->id,
            filter: AttendanceHistoryFilterData::fromArray($filters),
        );

        $records->getCollection()->transform(function ($attendance) {
            return $this->attendanceUiService->makeHistoryRow($attendance, true);
        });

        return $this->sharedView('attendance.admin.records', [
            'headerTitle' => 'Team Attendance Records',
            'headerSubtitle' => 'Lihat histori absensi bawahan yang relevan tanpa membuka akses lintas tim.',
            'records' => $records,
            'officeLocations' => $officeLocations,
            'employees' => $employees,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, int $attendance): View
    {
        $leader = $request->user();
        abort_if($leader === null, 401);

        $record = $this->teamAttendanceQueryService->getLeaderAttendanceDetail((int) $leader->id, $attendance);

        return $this->sharedView('attendance.admin.show', [
            'headerTitle' => 'Team Attendance Detail',
            'headerSubtitle' => 'Audit operasional absensi bawahan dengan scope yang dibatasi oleh division leader.',
            'detail' => $this->attendanceUiService->makeAttendanceDetail($record, includeSensitive: true),
        ]);
    }

    public function corrections(Request $request): View
    {
        $leader = $request->user();
        abort_if($leader === null, 401);

        $filters = [
            'status' => (string) $request->input('status', 'pending'),
            'office_location_id' => $request->filled('office_location_id') ? (int) $request->input('office_location_id') : null,
            'user_id' => $request->filled('user_id') ? (int) $request->input('user_id') : null,
        ];

        $officeLocations = $this->teamAttendanceQueryService->officeLocationsForLeader((int) $leader->id);
        $employees = $this->teamAttendanceQueryService->subordinateEmployees((int) $leader->id);
        $corrections = $this->attendanceCorrectionQueryService->getLeaderCorrections(
            leaderId: (int) $leader->id,
            filters: $filters,
        );

        return $this->sharedView('attendance.admin.corrections', [
            'headerTitle' => 'Team Attendance Corrections',
            'headerSubtitle' => 'Review correction request dari anggota tim yang berada di bawah leader aktif.',
            'officeLocations' => $officeLocations,
            'employees' => $employees,
            'selectedStatus' => $filters['status'],
            'statusOptions' => [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'all' => 'All',
            ],
            'corrections' => $corrections,
            'statusBadgeMap' => $this->correctionStatusBadgeMap(),
        ]);
    }

    public function showCorrection(Request $request, int $correction): View
    {
        $leader = $request->user();
        abort_if($leader === null, 401);

        $correctionModel = $this->attendanceCorrectionQueryService->getLeaderCorrectionDetail((int) $leader->id, $correction);

        return $this->sharedView('attendance.admin.correction-show', [
            'headerTitle' => 'Team Attendance Correction Detail',
            'headerSubtitle' => 'Bandingkan request koreksi dengan snapshot absensi sebelum memutuskan approve atau reject.',
            'correction' => $correctionModel,
            'detail' => $this->attendanceUiService->makeAttendanceDetail($correctionModel->attendance, includeSensitive: true),
            'statusBadgeMap' => $this->correctionStatusBadgeMap(),
            'originalSnapshot' => $this->normalizeCorrectionSnapshot($correctionModel->original_attendance_snapshot ?? []),
        ]);
    }

    public function reviewCorrection(ReviewAttendanceCorrectionRequest $request, int $correction): RedirectResponse
    {
        $leader = $request->user();
        abort_if($leader === null, 401);

        $correctionModel = $this->attendanceCorrectionQueryService->getLeaderCorrectionDetail((int) $leader->id, $correction);
        $action = $request->validated('action');
        $reviewerNote = blank($request->validated('reviewer_note')) ? null : (string) $request->validated('reviewer_note');

        try {
            if ($action === 'approve') {
                $this->attendanceCorrectionApprovalService->approve(
                    correction: $correctionModel,
                    reviewer: $leader,
                    reviewerNote: $reviewerNote,
                );

                $message = 'Attendance correction approved and applied to the attendance record.';
            } else {
                $this->attendanceCorrectionApprovalService->reject(
                    correction: $correctionModel,
                    reviewer: $leader,
                    reviewerNote: (string) $reviewerNote,
                );

                $message = 'Attendance correction rejected.';
            }
        } catch (AttendanceException $exception) {
            return redirect()
                ->route('approver.attendance.corrections.show', $correctionModel->id)
                ->with('error', $exception->getMessage())
                ->withInput();
        }

        return redirect()
            ->route('approver.attendance.corrections.show', $correctionModel->id)
            ->with('success', $message);
    }

    private function sharedView(string $view, array $data = []): View
    {
        return view($view, array_merge([
            'routePrefix' => 'approver',
            'layout' => 'components.approver.layout.layout-approver',
        ], $data));
    }

    private function historyFilterInput(Request $request): array
    {
        $data = $request->all();

        if (empty($data['sort_by'])) {
            $data['sort_by'] = 'created_at';
        }

        if (empty($data['sort_direction'])) {
            $data['sort_direction'] = 'desc';
        }

        return $data;
    }

    private function correctionStatusBadgeMap(): array
    {
        return [
            'pending' => [
                'label' => 'Pending',
                'icon' => 'fa-solid fa-clock',
                'pill_classes' => 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200',
            ],
            'approved' => [
                'label' => 'Approved',
                'icon' => 'fa-solid fa-circle-check',
                'pill_classes' => 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200',
            ],
            'rejected' => [
                'label' => 'Rejected',
                'icon' => 'fa-solid fa-circle-xmark',
                'pill_classes' => 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200',
            ],
        ];
    }

    private function normalizeCorrectionSnapshot(array $snapshot): array
    {
        return [
            'work_date' => $snapshot['work_date'] ?? '-',
            'check_in_at' => $this->formatSnapshotDateTime($snapshot['check_in_at'] ?? null),
            'check_out_at' => $this->formatSnapshotDateTime($snapshot['check_out_at'] ?? null),
            'check_in_status' => $snapshot['check_in_status'] ?? '-',
            'check_out_status' => $snapshot['check_out_status'] ?? '-',
            'record_status' => $snapshot['record_status'] ?? '-',
            'late_minutes' => (int) ($snapshot['late_minutes'] ?? 0),
            'early_leave_minutes' => (int) ($snapshot['early_leave_minutes'] ?? 0),
            'overtime_minutes' => (int) ($snapshot['overtime_minutes'] ?? 0),
            'is_suspicious' => (bool) ($snapshot['is_suspicious'] ?? false),
            'suspicious_reason' => $snapshot['suspicious_reason'] ?? null,
            'check_in_recorded_at' => $this->formatSnapshotDateTime($snapshot['check_in_recorded_at'] ?? null),
            'check_out_recorded_at' => $this->formatSnapshotDateTime($snapshot['check_out_recorded_at'] ?? null),
        ];
    }

    private function formatSnapshotDateTime(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->setTimezone('Asia/Jakarta')->translatedFormat('D, d M Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}


