<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legacy Export Toggle
    |--------------------------------------------------------------------------
    |
    | Legacy synchronous export routes and buttons are disabled by default.
    | Keep this false to ensure heavy exports are routed to async reporting.
    |
    */
    'legacy_export_enabled' => (bool) env('REPORT_LEGACY_EXPORT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Shared Report Storage
    |--------------------------------------------------------------------------
    */
    'shared_disk' => env('REPORT_SHARED_DISK', 'report_shared'),
    'shared_root' => env('REPORT_SHARED_ROOT', storage_path('app/report-shared')),

    /*
    |--------------------------------------------------------------------------
    | Async Reporting Dispatch
    |--------------------------------------------------------------------------
    */
    'dispatch_enabled' => (bool) env('REPORTING_DISPATCH_ENABLED', true),
    'internal_url' => env('REPORTING_INTERNAL_URL', ''),
    'internal_enqueue_path' => env('REPORTING_INTERNAL_ENQUEUE_PATH', '/api/internal/report-exports/enqueue'),
    'internal_timeout_seconds' => (int) env('REPORTING_INTERNAL_TIMEOUT_SECONDS', 10),
    'internal_client_id' => env('REPORTING_INTERNAL_CLIENT_ID', 'flowhr-main-app'),
    'internal_shared_secret' => env('REPORTING_INTERNAL_SHARED_SECRET', ''),
    'internal_clock_skew_seconds' => (int) env('REPORTING_INTERNAL_CLOCK_SKEW_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Summary Format Defaults
    |--------------------------------------------------------------------------
    */
    'summary_pdf_max_rows' => (int) env('REPORT_SUMMARY_PDF_MAX_ROWS', 300),
];
