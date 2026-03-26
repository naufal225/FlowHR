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
        $isMobileApiRequest = static fn(Request $request): bool => $request->is('api/mobile/*');
        $isLocalDevelopment = static fn(): bool => app()->environment(['local', 'development']) && (bool) config('app.debug');
        $debugContext = static function (Throwable $e) use ($isLocalDevelopment): array {
            if (! $isLocalDevelopment()) {
                return [];
            }

            $traceSteps = collect($e->getTrace())
                ->take(10)
                ->map(function (array $frame, int $index): array {
                    $class = $frame['class'] ?? null;
                    $type = $frame['type'] ?? null;
                    $function = $frame['function'] ?? 'unknown';
                    $file = $frame['file'] ?? null;
                    $line = $frame['line'] ?? null;

                    return [
                        'step' => $index + 1,
                        'call' => $class ? $class . $type . $function : $function,
                        'file' => $file,
                        'line' => $line,
                        'location' => $file && $line ? $file . ':' . $line : null,
                    ];
                })
                ->values()
                ->all();

            return [
                'debug' => [
                    'exception' => $e::class,
                    'error_message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'error_location' => $e->getFile() . ':' . $e->getLine(),
                    'trace' => $traceSteps,
                ],
            ];
        };

        // 1. HANDLE SEMUA AttendanceException (BASE) untuk API mobile
        $exceptions->render(function (
            AttendanceException $e,
            Request $request
        ) use ($isMobileApiRequest, $debugContext) {
            if (! $isMobileApiRequest($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getErrorCode(),
                ...$debugContext($e),
            ], $e->getStatusCode());
        });

        // 2. VALIDATION ERROR (Form Request / Validator) untuk API mobile
        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) use ($isMobileApiRequest, $debugContext) {
            if (! $isMobileApiRequest($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
                ...$debugContext($e),
            ], 422);
        });

        // 3. AUTH ERROR untuk API mobile
        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) use ($isMobileApiRequest, $debugContext) {
            if (! $isMobileApiRequest($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
                'code' => 'UNAUTHORIZED',
                ...$debugContext($e),
            ], 401);
        });

        // 4. FALLBACK (ERROR SYSTEM) untuk API mobile
        $exceptions->render(function (
            Throwable $e,
            Request $request
        ) use ($isMobileApiRequest, $debugContext, $isLocalDevelopment) {
            if (! $isMobileApiRequest($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $isLocalDevelopment() ? $e->getMessage() : 'Terjadi kesalahan pada server.',
                'code' => 'INTERNAL_SERVER_ERROR',
                ...$debugContext($e),
            ], 500);
        });

    })
    ->withCommands([
        \App\Console\Commands\RunQueueOnce::class,
    ])->create();
