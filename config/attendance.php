<?php

return [
    'qr_display' => [
        'session_ttl_days' => (int) env('ATTENDANCE_QR_DISPLAY_SESSION_TTL_DAYS', 30),
        'polling_interval_ms' => (int) env('ATTENDANCE_QR_DISPLAY_POLLING_INTERVAL_MS', 2500),
        'regenerate_grace_seconds' => (int) env('ATTENDANCE_QR_ROTATION_GRACE_SECONDS', 3),
        'touch_interval_seconds' => (int) env('ATTENDANCE_QR_DISPLAY_TOUCH_INTERVAL_SECONDS', 60),
    ],
];
