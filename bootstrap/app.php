<?php

use App\Http\Middleware\CheckFeatureActive;
use App\Http\Middleware\EnsureHasDivision;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

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
    ->withCommands([
        \App\Console\Commands\RunQueueOnce::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
