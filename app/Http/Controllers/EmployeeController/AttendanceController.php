<?php

declare(strict_types=1);

namespace App\Http\Controllers\EmployeeController;

use App\Data\Attendance\AttendanceHistoryFilterData;
use App\Exceptions\Attendance\AttendanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Services\Attendance\AttendanceDailyStatusResolverService;
use App\Services\Attendance\AttendanceDetailService;
use App\Services\Attendance\AttendanceHistoryService;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceUiService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceDailyStatusResolverService $dailyStatusResolverService,
        private readonly AttendanceHistoryService $attendanceHistoryService,
        private readonly AttendanceDetailService $attendanceDetailService,
        private readonly AttendancePolicyService $attendancePolicyService,
        private readonly AttendanceUiService $attendanceUiService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_if($user === null, 401);
        $user->loadMissing('officeLocation:id,name,address');

        $today = now('Asia/Jakarta')->startOfDay();
        $todayState = $this->buildTodayState($user, $today);
        $policySummary = null;
        $policyError = null;

        try {
            $policy = $this->attendancePolicyService->getPolicyForUser($user->id, $today);
            $policySummary = $this->attendanceUiService->makePolicySummary($policy, $user->officeLocation);
        } catch (AttendanceException $exception) {
            $policyError = $exception->getMessage();
        }

        $recentHistory = Attendance::query()
            ->with('officeLocation:id,name')
            ->where('user_id', $user->id)
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Attendance $attendance) => $this->attendanceUiService->makeHistoryRow($attendance))
            ->values();

        return view('employee.attendance_pages.overview_page', [
            'todayState' => $todayState,
            'policySummary' => $policySummary,
            'policyError' => $policyError,
            'recentHistory' => $recentHistory,
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $filters = $this->historyFilterInput($request);
        $filterData = AttendanceHistoryFilterData::fromArray($filters);
        $records = $this->attendanceHistoryService->getEmployeeHistory($user->id, $filterData);

        $records->getCollection()->transform(function ($attendance) {
            return $this->attendanceUiService->makeHistoryRow($attendance);
        });

        $recentCorrections = AttendanceCorrection::query()
            ->with('attendance:id,work_date')
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(6)
            ->get();

        return view('employee.attendance_pages.history_page', [
            'records' => $records,
            'filters' => $filters,
            'recentCorrections' => $recentCorrections,
        ]);
    }

    public function show(Request $request, int $attendance)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $record = $this->attendanceDetailService->getEmployeeAttendanceDetail($user->id, $attendance);
        $detail = $this->attendanceUiService->makeAttendanceDetail($record, includeSensitive: false);
        $corrections = AttendanceCorrection::query()
            ->where('user_id', $user->id)
            ->where('attendance_id', $record->id)
            ->latest('created_at')
            ->get();

        $hasPendingCorrection = $corrections->contains(fn (AttendanceCorrection $correction) => $correction->status === 'pending');

        return view('employee.attendance_pages.show_page', [
            'detail' => $detail,
            'attendanceRecord' => $record,
            'corrections' => $corrections,
            'hasPendingCorrection' => $hasPendingCorrection,
        ]);
    }

    public function storeCorrection(StoreAttendanceCorrectionRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $attendance = Attendance::query()
            ->where('id', (int) $request->input('attendance_record_id'))
            ->where('user_id', $user->id)
            ->first();

        if ($attendance === null) {
            return back()
                ->withErrors([
                    'attendance_record_id' => 'Log absensi tidak valid untuk user aktif.',
                ])
                ->withInput();
        }

        $requestedCheckInTime = $request->filled('requested_check_in_time')
            ? Carbon::parse((string) $request->input('requested_check_in_time'), 'Asia/Jakarta')
            : null;
        $requestedCheckOutTime = $request->filled('requested_check_out_time')
            ? Carbon::parse((string) $request->input('requested_check_out_time'), 'Asia/Jakarta')
            : null;

        if ($requestedCheckInTime !== null && $requestedCheckOutTime !== null && $requestedCheckOutTime->lt($requestedCheckInTime)) {
            return back()
                ->withErrors([
                    'requested_check_out_time' => 'Waktu check out koreksi tidak boleh lebih awal dari check in koreksi.',
                ])
                ->withInput();
        }

        if ($requestedCheckOutTime !== null && $requestedCheckInTime === null && $attendance->check_in_at === null) {
            return back()
                ->withErrors([
                    'requested_check_in_time' => 'Koreksi check-in wajib diisi jika log saat ini belum memiliki check-in.',
                ])
                ->withInput();
        }

        $hasPendingCorrection = AttendanceCorrection::query()
            ->where('user_id', $user->id)
            ->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingCorrection) {
            return back()
                ->withErrors([
                    'attendance_record_id' => 'Log absensi ini sudah memiliki pengajuan koreksi yang masih pending.',
                ])
                ->withInput();
        }

        AttendanceCorrection::query()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_check_in_time' => $requestedCheckInTime,
            'requested_check_out_time' => $requestedCheckOutTime,
            'reason' => (string) $request->input('reason'),
            'original_attendance_snapshot' => $this->attendanceSnapshot($attendance),
            'status' => 'pending',
        ]);

        return redirect()
            ->route('employee.attendance.show', $attendance->id)
            ->with('success', 'Pengajuan koreksi absensi berhasil dikirim.');
    }

    private function buildTodayState($user, Carbon $today): array
    {
        try {
            $status = $this->dailyStatusResolverService->resolveForUser($user, $today);

            return $this->attendanceUiService->makeDailyStatus($status);
        } catch (AttendanceException $exception) {
            return [
                'key' => 'config_issue',
                'badge' => $this->attendanceUiService->badgeFromStatus('config_issue'),
                'description' => $exception->getMessage(),
                'attendance_id' => null,
                'date_label' => $today->translatedFormat('D, d M Y'),
                'check_in' => '-',
                'check_out' => '-',
                'flags' => [],
            ];
        }
    }

    private function historyFilterInput(Request $request): array
    {
        $data = $request->all();

        if (empty($data['start_date']) && empty($data['end_date'])) {
            $data['start_date'] = now('Asia/Jakarta')->startOfMonth()->toDateString();
            $data['end_date'] = now('Asia/Jakarta')->toDateString();
        }

        return $data;
    }

    private function attendanceSnapshot(Attendance $attendance): array
    {
        return [
            'work_date' => $attendance->work_date?->toDateString(),
            'check_in_at' => $attendance->check_in_at?->toIso8601String(),
            'check_out_at' => $attendance->check_out_at?->toIso8601String(),
            'check_in_status' => $attendance->check_in_status?->value,
            'check_out_status' => $attendance->check_out_status?->value,
            'record_status' => $attendance->record_status?->value,
            'late_minutes' => (int) ($attendance->late_minutes ?? 0),
            'early_leave_minutes' => (int) ($attendance->early_leave_minutes ?? 0),
            'overtime_minutes' => (int) ($attendance->overtime_minutes ?? 0),
            'is_suspicious' => (bool) $attendance->is_suspicious,
            'suspicious_reason' => $attendance->suspicious_reason,
            'check_in_recorded_at' => $attendance->check_in_recorded_at?->toIso8601String(),
            'check_out_recorded_at' => $attendance->check_out_recorded_at?->toIso8601String(),
        ];
    }
}
