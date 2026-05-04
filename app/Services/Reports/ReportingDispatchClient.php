<?php

namespace App\Services\Reports;

use App\Models\ReportExport;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ReportingDispatchClient
{
    /**
     * @throws RuntimeException
     */
    public function enqueue(ReportExport $reportExport): void
    {
        if (! (bool) config('reporting.dispatch_enabled', true)) {
            throw new RuntimeException('Reporting dispatch is currently disabled.');
        }

        $baseUrl = trim((string) config('reporting.internal_url', ''));
        $enqueuePath = trim((string) config('reporting.internal_enqueue_path', '/api/internal/report-exports/enqueue'));
        $sharedSecret = (string) config('reporting.internal_shared_secret', '');
        $clientId = (string) config('reporting.internal_client_id', 'flowhr-main-app');
        $timeoutSeconds = max(1, (int) config('reporting.internal_timeout_seconds', 10));

        if ($baseUrl === '' || $sharedSecret === '') {
            throw new RuntimeException('Reporting internal endpoint configuration is incomplete.');
        }

        if ($enqueuePath === '') {
            throw new RuntimeException('Reporting enqueue path is empty.');
        }

        $requestUrl = rtrim($baseUrl, '/') . '/' . ltrim($enqueuePath, '/');

        $payload = [
            'report_export_id' => (string) $reportExport->id,
            'module' => (string) $reportExport->module,
            'export_type' => (string) $reportExport->export_type,
            'format' => (string) $reportExport->format,
            'requested_by' => (int) $reportExport->requested_by,
            'role_scope' => (string) $reportExport->role_scope,
        ];

        $timestamp = (string) now('UTC')->timestamp;
        $nonce = (string) Str::uuid();
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

        if ($payloadJson === false) {
            throw new RuntimeException('Failed to encode reporting dispatch payload.');
        }

        $signature = hash_hmac('sha256', $timestamp . '.' . $nonce . '.' . $payloadJson, $sharedSecret);

        try {
            $response = Http::timeout($timeoutSeconds)
                ->acceptJson()
                ->withHeaders([
                    'X-Reporting-Client' => $clientId,
                    'X-Reporting-Timestamp' => $timestamp,
                    'X-Reporting-Nonce' => $nonce,
                    'X-Reporting-Signature' => $signature,
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->post($requestUrl, $payload);

            if ($response->status() === 409) {
                return;
            }

            $response->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException(
                'Failed to enqueue export to reporting app: ' . $exception->getMessage(),
                previous: $exception
            );
        }
    }
}
