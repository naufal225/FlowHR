<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Dashboard\DashboardAttendanceStateService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        View::composer([
            'admin.dashboard.index',
            'super-admin.dashboard.index',
            'manager.dashboard.index',
            'approver.dashboard.index',
            'Employee.index',
            'employee.index',
            'Finance.index',
            'finance.index',
        ], function ($view): void {
            /** @var DashboardAttendanceStateService $stateService */
            $stateService = app(DashboardAttendanceStateService::class);
            $user = auth()->user();

            if (! $user instanceof User) {
                $view->with('dashboardAttendanceState', $stateService->fallback(
                    description: 'User context is unavailable, attendance state cannot be resolved.'
                ));

                return;
            }

            $view->with('dashboardAttendanceState', $stateService->forUser($user));
        });
    }
}
