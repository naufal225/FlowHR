<?php

namespace App\Http\Controllers;

use App\Models\ReportExport;
use App\Services\Reports\ReportExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReportExportController extends Controller
{
    public function __construct(
        private readonly ReportExportService $reportExportService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $roleScope = (string) $request->route('role_scope');

        $validated = $request->validate([
            'module' => ['required', Rule::in([
                ReportExport::MODULE_REIMBURSEMENT,
                ReportExport::MODULE_OVERTIME,
                ReportExport::MODULE_OFFICIAL_TRAVEL,
            ])],
            'export_type' => ['required', Rule::in([
                ReportExport::EXPORT_TYPE_SUMMARY,
                ReportExport::EXPORT_TYPE_EVIDENCE,
            ])],
            'format' => ['nullable', Rule::in([
                ReportExport::FORMAT_PDF,
                ReportExport::FORMAT_XLSX,
                ReportExport::FORMAT_ZIP,
            ])],
            'filters.status' => ['nullable', Rule::in(['approved', 'rejected', 'pending'])],
            'filters.from_date' => ['required', 'date'],
            'filters.to_date' => ['required', 'date', 'after_or_equal:filters.from_date'],
        ]);

        $format = isset($validated['format']) ? (string) $validated['format'] : '';
        $exportType = (string) $validated['export_type'];
        if ($exportType === ReportExport::EXPORT_TYPE_SUMMARY && $format === ReportExport::FORMAT_ZIP) {
            return response()->json([
                'message' => 'Summary export only supports pdf or xlsx format.',
            ], 422);
        }

        if ($exportType === ReportExport::EXPORT_TYPE_EVIDENCE && $format !== '' && $format !== ReportExport::FORMAT_ZIP) {
            return response()->json([
                'message' => 'Evidence export must use zip format.',
            ], 422);
        }

        $from = Carbon::parse((string) $validated['filters']['from_date'], 'Asia/Jakarta')->startOfDay();
        $to = Carbon::parse((string) $validated['filters']['to_date'], 'Asia/Jakarta')->startOfDay();
        $maxRangeInDays = 31;
        if ($from->diffInDays($to) > $maxRangeInDays) {
            return response()->json([
                'message' => 'Maximum export range is 31 days.',
            ], 422);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $reportExport = $this->reportExportService->createExport($user, $roleScope, $validated);

        return response()->json([
            'data' => $this->mapExportPayload($reportExport->fresh(), $roleScope),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $roleScope = (string) $request->route('role_scope');
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $exports = ReportExport::query()
            ->where('requested_by', $user->id)
            ->where('role_scope', $roleScope)
            ->latest('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $exports->map(
                fn (ReportExport $export): array => $this->mapExportPayload($export, $roleScope)
            )->values(),
        ]);
    }

    public function show(Request $request, ReportExport $reportExport): JsonResponse
    {
        $roleScope = (string) $request->route('role_scope');
        $this->authorizeAccess($request, $reportExport, $roleScope);

        return response()->json([
            'data' => $this->mapExportPayload($reportExport, $roleScope),
        ]);
    }

    public function download(Request $request, ReportExport $reportExport)
    {
        $roleScope = (string) $request->route('role_scope');
        $this->authorizeAccess($request, $reportExport, $roleScope);

        if ($reportExport->status !== ReportExport::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Report export is not completed yet.',
            ], 409);
        }

        if (! $reportExport->result_disk || ! $reportExport->result_path) {
            return response()->json([
                'message' => 'Generated file metadata is missing.',
            ], 404);
        }

        if (! Storage::disk($reportExport->result_disk)->exists($reportExport->result_path)) {
            return response()->json([
                'message' => 'Generated file not found.',
            ], 404);
        }

        return Storage::disk($reportExport->result_disk)->download(
            $reportExport->result_path,
            $reportExport->file_name ?? basename($reportExport->result_path)
        );
    }

    private function authorizeAccess(Request $request, ReportExport $reportExport, string $roleScope): void
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        abort_unless(
            (int) $reportExport->requested_by === (int) $user->id &&
            $reportExport->role_scope === $roleScope,
            404
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function mapExportPayload(ReportExport $reportExport, string $roleScope): array
    {
        $downloadRouteName = $roleScope . '.report-exports.download';

        return [
            'id' => $reportExport->id,
            'module' => $reportExport->module,
            'export_type' => $reportExport->export_type,
            'format' => $reportExport->format,
            'status' => $reportExport->status,
            'progress_percent' => (int) $reportExport->progress_percent,
            'processed_items' => (int) $reportExport->processed_items,
            'total_items' => (int) $reportExport->total_items,
            'file_name' => $reportExport->file_name,
            'error_message' => $reportExport->error_message,
            'download_url' => $reportExport->status === ReportExport::STATUS_COMPLETED
                ? route($downloadRouteName, ['reportExport' => $reportExport->id])
                : null,
            'created_at' => optional($reportExport->created_at)->toIso8601String(),
            'updated_at' => optional($reportExport->updated_at)->toIso8601String(),
            'started_at' => optional($reportExport->started_at)->toIso8601String(),
            'finished_at' => optional($reportExport->finished_at)->toIso8601String(),
        ];
    }
}
