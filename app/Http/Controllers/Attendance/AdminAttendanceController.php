<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Data\Attendance\AttendanceHistoryFilterData;
use App\Enums\Roles;
use App\Exceptions\Attendance\AttendanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AttendanceQrActionRequest;
use App\Http\Requests\Attendance\UpsertAttendanceSettingRequest;
use App\Models\AttendanceSetting;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Services\Attendance\AttendanceDailyStatusResolverService;
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
use Illuminate\Support\Str;

class AdminAttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceDailyStatusResolverService $dailyStatusResolverService,
        private readonly AttendanceHistoryService $attendanceHistoryService,
        private readonly AttendanceDetailService $attendanceDetailService,
        private readonly AttendanceUiService $attendanceUiService,
        private readonly AttendanceQrManagementService $attendanceQrManagementService,
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

    public function qr(Request $request): View
    {
        $officeLocations = $this->officeLocations();
        $selectedOffice = $this->resolveSelectedOffice($request, $officeLocations);

        return $this->sharedView('attendance.admin.qr', [
            'headerTitle' => 'Attendance QR',
            'headerSubtitle' => 'Monitor the currently active QR token for attendance scanning.',
            'officeLocations' => $officeLocations,
            'selectedOffice' => $selectedOffice,
            'qrCard' => $this->currentQrCardData($selectedOffice),
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
        ]);
    }

    public function updateSettings(UpsertAttendanceSettingRequest $request): RedirectResponse
    {
        $setting = $this->attendanceSettingManagementService->upsert($request->validated());

        return redirect()
            ->route($this->routeName('attendance.settings'), ['office_location_id' => $setting->office_location_id])
            ->with('success', 'Attendance setting berhasil disimpan.');
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
            ->select(['id', 'name', 'address', 'radius_meter', 'timezone', 'is_active'])
            ->orderBy('name')
            ->get();
    }

    private function resolveSelectedOffice(Request $request, Collection $officeLocations): ?OfficeLocation
    {
        $requestedOfficeId = $request->filled('office_location_id')
            ? (int) $request->input('office_location_id')
            : null;

        if ($requestedOfficeId !== null) {
            return $officeLocations->firstWhere('id', $requestedOfficeId);
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

        if (empty($data['start_date']) && empty($data['end_date'])) {
            $data['start_date'] = now('Asia/Jakarta')->startOfMonth()->toDateString();
            $data['end_date'] = now('Asia/Jakarta')->toDateString();
        }

        return $data;
    }

    private function currentQrCardData(?OfficeLocation $office): array
    {
        $currentSetting = $office?->activeAttendanceSetting()->latest('id')->first();
        $currentToken = $office !== null
            ? $this->attendanceQrManagementService->ensureCurrentForOffice($office)
            : null;

        return $this->attendanceUiService->makeQrCard($currentToken, $office, $currentSetting);
    }
}



