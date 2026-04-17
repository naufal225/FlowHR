<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Data\Attendance\AttendanceHistoryFilterData;
use App\Enums\Roles;
use App\Exceptions\Attendance\AttendanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AttendanceQrActionRequest;
use App\Http\Requests\Attendance\RevokeAttendanceQrDisplaySessionRequest;
use App\Http\Requests\Attendance\ReviewAttendanceCorrectionRequest;
use App\Http\Requests\Attendance\StoreAttendanceQrDisplaySessionRequest;
use App\Http\Requests\Attendance\UpsertAttendanceSettingRequest;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceQrDisplaySession;
use App\Models\AttendanceSetting;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Services\Attendance\AttendanceCorrectionApprovalService;
use App\Services\Attendance\AttendanceDailyStatusResolverService;
use App\Services\Attendance\AttendanceQrDisplaySessionService;
use App\Services\Attendance\AttendanceDetailService;
use App\Services\Attendance\AttendanceHistoryService;
use App\Services\Attendance\AttendanceQrManagementService;
use App\Services\Attendance\AttendanceSettingManagementService;
use App\Services\Attendance\AttendanceUiService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AdminAttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceDailyStatusResolverService $dailyStatusResolverService,
        private readonly AttendanceHistoryService $attendanceHistoryService,
        private readonly AttendanceDetailService $attendanceDetailService,
        private readonly AttendanceUiService $attendanceUiService,
        private readonly AttendanceCorrectionApprovalService $attendanceCorrectionApprovalService,
        private readonly AttendanceQrManagementService $attendanceQrManagementService,
        private readonly AttendanceQrDisplaySessionService $attendanceQrDisplaySessionService,
        private readonly AttendanceSettingManagementService $attendanceSettingManagementService,
    ) {}

    public function index(Request $request): View
    {
        $officeLocations = $this->officeLocations();
        $selectedOffice = $this->resolveSelectedOffice($request, $officeLocations);
        $quickFilter = (string) $request->input('quick', 'all');
        $today = now('Asia/Jakarta')->startOfDay();

        $employees = $this->employeeQuery($selectedOffice?->id)->get();
        $rows = $this->buildMonitoringRows($employees, $today);
        $filteredRows = $this->applyQuickFilter($rows, $quickFilter);

        $stats = [
            'total' => $rows->count(),
            'checked_in' => $rows->filter(fn (array $row) => $row['has_check_in'])->count(),
            'late' => $rows->filter(fn (array $row) => $row['is_late'])->count(),
            'not_checked_in' => $rows->filter(fn (array $row) => in_array($row['status_key'], ['not_checked_in', 'absent'], true))->count(),
            'complete' => $rows->filter(fn (array $row) => $row['status_key'] === 'complete')->count(),
            'suspicious' => $rows->filter(fn (array $row) => $row['is_suspicious'] || $row['status_key'] === 'config_issue')->count(),
        ];

        $priorityIssues = $rows
            ->filter(fn (array $row) => $row['is_suspicious']
                || $row['is_late']
                || in_array($row['status_key'], ['not_checked_in', 'absent', 'config_issue'], true))
            ->take(6)
            ->values();

        return $this->sharedView('attendance.admin.overview', [
            'headerTitle' => 'Attendance Overview',
            'headerSubtitle' => 'Monitoring attendance status for the current workday.',
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
            'stats' => $stats,
            'rows' => $filteredRows,
            'priorityIssues' => $priorityIssues,
        ]);
    }

    public function records(Request $request): View
    {
        $officeLocations = $this->officeLocations();
        $employees = $this->employeeQuery()->get();
        $filters = $this->historyFilterInput($request);
        $filter = AttendanceHistoryFilterData::fromArray($filters);
        $records = $this->attendanceHistoryService->getAllEmployeeHistory($filter);

        $records->getCollection()->transform(function ($attendance) {
            return $this->attendanceUiService->makeHistoryRow($attendance, true);
        });

        return $this->sharedView('attendance.admin.records', [
            'headerTitle' => 'Attendance Records',
            'headerSubtitle' => 'Browse attendance records with practical operational filters.',
            'records' => $records,
            'officeLocations' => $officeLocations,
            'employees' => $employees,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, int $attendance): View
    {
        $record = $this->attendanceDetailService->getAttendanceDetailForAdmin($attendance);
        $record->loadMissing('user.division:id,name');

        return $this->sharedView('attendance.admin.show', [
            'headerTitle' => 'Attendance Detail',
            'headerSubtitle' => 'Audit a single attendance record with full operational context.',
            'detail' => $this->attendanceUiService->makeAttendanceDetail($record, includeSensitive: true),
        ]);
    }

    public function corrections(Request $request): View
    {
        $status = (string) $request->input('status', 'pending');
        $allowedStatuses = ['pending', 'approved', 'rejected', 'all'];
        $employeeName = trim((string) $request->input('employee_name', ''));

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $officeLocations = $this->officeLocations();

        $corrections = AttendanceCorrection::query()
            ->with([
                'attendance:id,user_id,office_location_id,work_date,check_in_at,check_out_at',
                'attendance.user:id,name,email',
                'attendance.officeLocation:id,name',
                'reviewer:id,name',
            ])
            ->when($request->filled('office_location_id'), function ($query) use ($request) {
                $query->whereHas('attendance', function ($attendanceQuery) use ($request) {
                    $attendanceQuery->where('office_location_id', (int) $request->input('office_location_id'));
                });
            })
            ->when($employeeName !== '', function ($query) use ($employeeName) {
                $query->whereHas('attendance.user', function ($userQuery) use ($employeeName) {
                    $userQuery->where('name', 'like', '%' . $employeeName . '%');
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return $this->sharedView('attendance.admin.corrections', [
            'headerTitle' => 'Attendance Corrections',
            'headerSubtitle' => 'Review and process attendance correction requests without touching approval flows in other modules.',
            'officeLocations' => $officeLocations,
            'selectedStatus' => $status,
            'employeeNameFilter' => $employeeName,
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

    public function showCorrection(int $correction): View
    {
        $correctionModel = AttendanceCorrection::query()
            ->with([
                'attendance.user.division:id,name',
                'attendance.officeLocation.activeAttendanceSetting:id,office_location_id,work_start_time,work_end_time,late_tolerance_minutes,is_active',
                'attendance.attendanceQrToken:id,office_location_id,generated_at,expired_at,is_active,token',
                'attendance.logs' => fn ($query) => $query->latest('occurred_at')->latest('id'),
                'reviewer:id,name',
            ])
            ->findOrFail($correction);

        return $this->sharedView('attendance.admin.correction-show', [
            'headerTitle' => 'Attendance Correction Detail',
            'headerSubtitle' => 'Compare requested correction against the original and current attendance record before reviewing.',
            'correction' => $correctionModel,
            'detail' => $this->attendanceUiService->makeAttendanceDetail($correctionModel->attendance, includeSensitive: true),
            'statusBadgeMap' => $this->correctionStatusBadgeMap(),
            'originalSnapshot' => $this->normalizeCorrectionSnapshot($correctionModel->original_attendance_snapshot ?? []),
        ]);
    }

    public function reviewCorrection(ReviewAttendanceCorrectionRequest $request, int $correction): RedirectResponse
    {
        $correctionModel = AttendanceCorrection::query()->findOrFail($correction);
        $action = $request->validated('action');
        $reviewerNote = blank($request->validated('reviewer_note')) ? null : (string) $request->validated('reviewer_note');

        try {
            if ($action === 'approve') {
                $this->attendanceCorrectionApprovalService->approve(
                    correction: $correctionModel,
                    reviewer: Auth::user(),
                    reviewerNote: $reviewerNote,
                );

                $message = 'Attendance correction approved and applied to the attendance record.';
            } else {
                $this->attendanceCorrectionApprovalService->reject(
                    correction: $correctionModel,
                    reviewer: Auth::user(),
                    reviewerNote: (string) $reviewerNote,
                );

                $message = 'Attendance correction rejected.';
            }
        } catch (AttendanceException $exception) {
            return redirect()
                ->route($this->routeName('attendance.corrections.show'), $correctionModel->id)
                ->with('error', $exception->getMessage())
                ->withInput();
        }

        return redirect()
            ->route($this->routeName('attendance.corrections.show'), $correctionModel->id)
            ->with('success', $message);
    }

    public function qr(Request $request): View
    {
        $officeLocations = $this->officeLocations();
        $selectedOffice = $this->resolveSelectedOffice($request, $officeLocations);
        $defaultTtlDays = max(1, (int) config('attendance.qr_display.session_ttl_days', 30));
        $officeTimezone = (string) ($selectedOffice?->timezone ?: config('app.timezone', 'Asia/Jakarta'));
        $defaultExpiresAt = now($officeTimezone)->addDays($defaultTtlDays)->startOfMinute();
        $minimumExpiresAt = now($officeTimezone)->addMinute()->startOfMinute();

        return $this->sharedView('attendance.admin.qr', [
            'headerTitle' => 'Attendance QR',
            'headerSubtitle' => 'Monitor the currently active QR token for attendance scanning.',
            'officeLocations' => $officeLocations,
            'selectedOffice' => $selectedOffice,
            'qrCard' => $this->currentQrCardData($selectedOffice),
            'displaySessions' => $this->displaySessionsForOffice($selectedOffice),
            'displaySessionDefaults' => [
                'expires_at' => $defaultExpiresAt->format('Y-m-d\TH:i'),
                'min_expires_at' => $minimumExpiresAt->format('Y-m-d\TH:i'),
                'timezone' => $officeTimezone,
            ],
            'generatedDisplayUrl' => (string) session('attendance_qr_display_url', ''),
        ]);
    }

    public function qrStatus(Request $request): JsonResponse
    {
        $officeLocations = $this->officeLocations();
        $selectedOffice = $this->resolveSelectedOffice($request, $officeLocations);

        return response()->json([
            'qrCard' => $this->currentQrCardData($selectedOffice),
        ]);
    }

    public function regenerateQr(AttendanceQrActionRequest $request): RedirectResponse
    {
        $office = OfficeLocation::query()->findOrFail((int) $request->validated('office_location_id'));

        try {
            $this->attendanceQrManagementService->regenerate($office);
        } catch (AttendanceException $exception) {
            return redirect()
                ->route($this->routeName('attendance.qr'), ['office_location_id' => $office->id])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route($this->routeName('attendance.qr'), ['office_location_id' => $office->id])
            ->with('success', 'Attendance QR berhasil diperbarui.');
    }

    public function invalidateQr(AttendanceQrActionRequest $request): RedirectResponse
    {
        $office = OfficeLocation::query()->findOrFail((int) $request->validated('office_location_id'));

        $this->attendanceQrManagementService->invalidate($office);

        return redirect()
            ->route($this->routeName('attendance.qr'), ['office_location_id' => $office->id])
            ->with('success', 'QR aktif untuk office terpilih sudah dinonaktifkan.');
    }

    public function settings(Request $request): View
    {
        $officeLocations = $this->officeLocations();
        $selectedOffice = $this->resolveSelectedOffice($request, $officeLocations);
        $currentSetting = $selectedOffice
            ? AttendanceSetting::query()
                ->where('office_location_id', $selectedOffice->id)
                ->latest('id')
                ->first()
            : null;

        return $this->sharedView('attendance.admin.settings', [
            'headerTitle' => 'Attendance Settings',
            'headerSubtitle' => 'Configure attendance policy per office without touching core attendance logic.',
            'officeLocations' => $officeLocations,
            'selectedOffice' => $selectedOffice,
            'currentSetting' => $currentSetting,
            'settingSummary' => $this->attendanceUiService->makeSettingSummary($currentSetting, $selectedOffice),
            'settingsForm' => $this->settingsFormData($selectedOffice, $currentSetting),
        ]);
    }

    public function updateSettings(UpsertAttendanceSettingRequest $request): RedirectResponse
    {
        $setting = $this->attendanceSettingManagementService->upsert($request->validated());

        return redirect()
            ->route($this->routeName('attendance.settings'), ['office_location_id' => $setting->office_location_id])
            ->with('success', 'Attendance setting berhasil disimpan.');
    }

    public function storeDisplaySession(StoreAttendanceQrDisplaySessionRequest $request): RedirectResponse
    {
        $office = OfficeLocation::query()->findOrFail((int) $request->validated('office_location_id'));
        $actor = Auth::user();
        $officeTimezone = (string) ($office->timezone ?: config('app.timezone', 'Asia/Jakarta'));
        $expiresAt = null;

        if ($request->filled('expires_at')) {
            $parsedExpiresAt = Carbon::createFromFormat('Y-m-d\TH:i', (string) $request->validated('expires_at'), $officeTimezone);
            $expiresAt = $parsedExpiresAt instanceof Carbon ? $parsedExpiresAt : null;
        }

        if (! $actor instanceof User) {
            abort(403, 'Unauthorized actor.');
        }

        $created = $this->attendanceQrDisplaySessionService->create(
            office: $office,
            actor: $actor,
            name: (string) $request->validated('name'),
            ttlDays: $request->filled('ttl_days') ? (int) $request->validated('ttl_days') : null,
            expiresAt: $expiresAt,
        );

        return redirect()
            ->route($this->routeName('attendance.qr'), ['office_location_id' => $office->id])
            ->with('success', 'QR display session berhasil dibuat. Simpan link TV sekarang karena link hanya ditampilkan sekali.')
            ->with('attendance_qr_display_url', $created['display_url']);
    }

    public function revokeDisplaySession(RevokeAttendanceQrDisplaySessionRequest $request, int $displaySession): RedirectResponse
    {
        $officeId = (int) $request->validated('office_location_id');
        $session = AttendanceQrDisplaySession::query()
            ->where('office_location_id', $officeId)
            ->findOrFail($displaySession);

        $this->attendanceQrDisplaySessionService->revoke($session);

        return redirect()
            ->route($this->routeName('attendance.qr'), ['office_location_id' => $officeId])
            ->with('success', 'QR display session berhasil di-revoke.');
    }

    private function sharedView(string $view, array $data = []): View
    {
        return view($view, array_merge($this->context(), $data));
    }

    private function context(): array
    {
        $rolePrefix = Str::before((string) request()->route()?->getName(), '.');

        return [
            'routePrefix' => $rolePrefix,
            'layout' => $rolePrefix === 'super-admin'
                ? 'components.super-admin.layout.layout-super-admin'
                : 'components.admin.layout.layout-admin',
        ];
    }

    private function routeName(string $suffix): string
    {
        return $this->context()['routePrefix'] . '.' . $suffix;
    }

    private function officeLocations(): Collection
    {
        return OfficeLocation::query()
            ->select(['id', 'code', 'name', 'address', 'latitude', 'longitude', 'radius_meter', 'timezone', 'is_active'])
            ->orderBy('name')
            ->get();
    }

    private function resolveSelectedOffice(Request $request, Collection $officeLocations): ?OfficeLocation
    {
        $requestedOfficeId = old('office_location_id');

        if ($requestedOfficeId === null && $request->filled('office_location_id')) {
            $requestedOfficeId = (int) $request->input('office_location_id');
        }

        if ($requestedOfficeId !== null) {
            return $officeLocations->firstWhere('id', (int) $requestedOfficeId);
        }

        return $officeLocations->first();
    }

    private function employeeQuery(?int $officeLocationId = null)
    {
        return User::query()
            ->select(['id', 'name', 'email', 'office_location_id', 'division_id', 'is_active'])
            ->with([
                'officeLocation:id,name',
                'division:id,name',
            ])
            ->where('is_active', true)
            ->when($officeLocationId !== null, fn ($query) => $query->where('office_location_id', $officeLocationId))
            ->whereHas('roles', fn ($query) => $query->where('name', Roles::Employee->value))
            ->orderBy('name');
    }

    private function buildMonitoringRows(Collection $employees, Carbon $date): Collection
    {
        return $employees->map(function (User $employee) use ($date): array {
            try {
                $status = $this->dailyStatusResolverService->resolveForUser($employee, $date);

                return $this->attendanceUiService->makeMonitorRow($employee, $status);
            } catch (AttendanceException $exception) {
                return $this->attendanceUiService->makeMonitorErrorRow($employee, $exception->getMessage());
            }
        });
    }

    private function applyQuickFilter(Collection $rows, string $quickFilter): Collection
    {
        return match ($quickFilter) {
            'checked_in' => $rows->filter(fn (array $row) => $row['has_check_in'])->values(),
            'late' => $rows->filter(fn (array $row) => $row['is_late'])->values(),
            'not_checked_in' => $rows->filter(fn (array $row) => in_array($row['status_key'], ['not_checked_in', 'absent'], true))->values(),
            'suspicious' => $rows->filter(fn (array $row) => $row['is_suspicious'] || $row['status_key'] === 'config_issue')->values(),
            default => $rows->values(),
        };
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

    private function settingsFormData(?OfficeLocation $selectedOffice, ?AttendanceSetting $currentSetting): array
    {
        return [
            'filter_action' => route($this->routeName('attendance.settings')),
            'submit_action' => route($this->routeName('attendance.settings.update')),
            'reset_href' => route($this->routeName('attendance.settings'), array_filter([
                'office_location_id' => $selectedOffice?->id,
            ])),
            'selected_office_id' => (int) old('office_location_id', $selectedOffice?->id),
            'selected_office' => $selectedOffice,
            'values' => [
                'work_start_time' => old('work_start_time', $this->normalizeTimeInput($currentSetting?->work_start_time, '09:00')),
                'work_end_time' => old('work_end_time', $this->normalizeTimeInput($currentSetting?->work_end_time, '17:00')),
                'late_tolerance_minutes' => (int) old('late_tolerance_minutes', $currentSetting?->late_tolerance_minutes ?? 15),
                'qr_rotation_seconds' => (int) old('qr_rotation_seconds', $currentSetting?->qr_rotation_seconds ?? 30),
                'min_location_accuracy_meter' => (int) old('min_location_accuracy_meter', $currentSetting?->min_location_accuracy_meter ?? 50),
                'is_active' => filter_var(old('is_active', $currentSetting?->is_active ?? true), FILTER_VALIDATE_BOOLEAN),
            ],
        ];
    }

    private function normalizeTimeInput(?string $value, string $fallback): string
    {
        if (blank($value)) {
            return $fallback;
        }

        try {
            return Carbon::createFromFormat('H:i:s', $value)->format('H:i');
        } catch (\Throwable) {
            try {
                return Carbon::createFromFormat('H:i', $value)->format('H:i');
            } catch (\Throwable) {
                return $fallback;
            }
        }
    }

    private function currentQrCardData(?OfficeLocation $office): array
    {
        $currentSetting = $office?->activeAttendanceSetting()->latest('id')->first();
        $currentToken = $office !== null
            ? $this->attendanceQrManagementService->ensureCurrentForOffice($office)
            : null;

        return $this->attendanceUiService->makeQrCard($currentToken, $office, $currentSetting);
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

    private function displaySessionsForOffice(?OfficeLocation $office): Collection
    {
        if ($office === null) {
            return collect();
        }

        return AttendanceQrDisplaySession::query()
            ->where('office_location_id', $office->id)
            ->latest('id')
            ->limit(20)
            ->get(['id', 'office_location_id', 'name', 'token_encrypted', 'expires_at', 'revoked_at', 'last_seen_at', 'created_by', 'created_at'])
            ->map(function (AttendanceQrDisplaySession $session): AttendanceQrDisplaySession {
                $session->setAttribute('display_url', $this->attendanceQrDisplaySessionService->makeDisplayUrlForSession($session));

                return $session;
            });
    }
}



