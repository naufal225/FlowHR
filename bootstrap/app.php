<?php

use App\Exceptions\Attendance\AttendanceException;
use App\Http\Middleware\CheckFeatureActive;
use App\Http\Middleware\EnsureHasDivision;
use App\Http\Middleware\EnsureLegacyReportExportEnabled;
use App\Http\Middleware\EnsureMobileEmployeeAccess;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\ValidateAttendanceQrDisplaySession;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('queue:once')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('report-exports:cleanup')
            ->dailyAt('02:00')
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
            'role' => RoleMiddleware::class,
            'feature' => CheckFeatureActive::class,
            'division' => EnsureHasDivision::class,
            'mobile.employee' => EnsureMobileEmployeeAccess::class,
            'qr.display.session' => ValidateAttendanceQrDisplaySession::class,
            'legacy.export' => EnsureLegacyReportExportEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isMobileApiRequest = static fn (Request $request): bool => $request->is('api/mobile/*');
        $authNoCacheHeaders = static fn (): array => [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Fri, 01 Jan 1990 00:00:00 GMT',
        ];
        $isLocalDevelopment = static fn (): bool => app()->environment(['local', 'development']) && (bool) config('app.debug');
        $debugContext = static function (\Throwable $e) use ($isLocalDevelopment): array {
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

        $exceptions->render(function (
            HttpExceptionInterface $e,
            Request $request
        ) use ($isMobileApiRequest, $authNoCacheHeaders) {
            if ($isMobileApiRequest($request)) {
                return null;
            }

            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()
                ->route('login')
                ->with('error', 'Sesi keamanan halaman sudah kedaluwarsa. Silakan login ulang.')
                ->withHeaders($authNoCacheHeaders());
        });

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

        $exceptions->render(function (
            ModelNotFoundException $e,
            Request $request
        ) use ($isMobileApiRequest, $debugContext) {
            if (! $isMobileApiRequest($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Resource tidak ditemukan.',
                'code' => 'NOT_FOUND',
                ...$debugContext($e),
            ], 404);
        });

        $exceptions->render(function (
            HttpExceptionInterface $e,
            Request $request
        ) use ($isMobileApiRequest, $debugContext) {
            if (! $isMobileApiRequest($request)) {
                return null;
            }

            $statusCode = $e->getStatusCode();
            $code = match ($statusCode) {
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                default => 'HTTP_EXCEPTION',
            };

            $message = $statusCode === 404
                ? 'Resource tidak ditemukan.'
                : ($e->getMessage() !== '' ? $e->getMessage() : 'HTTP error.');

            return response()->json([
                'success' => false,
                'message' => $message,
                'code' => $code,
                ...$debugContext($e),
            ], $statusCode);
        });

        $exceptions->render(function (
            \Throwable $e,
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
        \App\Console\Commands\CleanupReportExportsCommand::class,
    ])->create();

