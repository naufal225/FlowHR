<?php

use App\Exceptions\Attendance\AttendanceException;
use App\Http\Middleware\CheckFeatureActive;
use App\Http\Middleware\EnsureHasDivision;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function (Schedule $schedule) {
        // Menjalankan command custom "queue:once" setiap menit
        $schedule->command('queue:once')
            ->everyMinute()
            ->withoutOverlapping();
    })
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            "role" => RoleMiddleware::class,
            "feature" => CheckFeatureActive::class,
            "division" => EnsureHasDivision::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
       // 1. HANDLE SEMUA AttendanceException (BASE)
        $exceptions->render(function (
            AttendanceException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getErrorCode(),
            ], $e->getStatusCode());
        });

        // 2. VALIDATION ERROR (Form Request / Validator)
        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
            ], 422);
        });

        // 3. AUTH ERROR API MOBILE
        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
                'code' => 'UNAUTHORIZED',
            ], 401);
        });

        // 4. FALLBACK (ERROR SYSTEM)
        $exceptions->render(function (
            Throwable $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.',
                'code' => 'INTERNAL_SERVER_ERROR',
            ], 500);
        });

    })
    ->withCommands([
        \App\Console\Commands\RunQueueOnce::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
