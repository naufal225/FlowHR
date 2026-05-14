<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CostSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveBalancesController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\OfficeLocationController;
use App\Http\Controllers\OfficialTravelController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\ReimbursementTypeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'division'])->group(function () {

    // ── Dashboard ──────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Attendance ─────────────────────────────────────────────────────────
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/',        [AttendanceController::class, 'index'])->name('index');
        Route::get('/history', [AttendanceController::class, 'history'])->name('history');
        Route::get('/team',    [AttendanceController::class, 'team'])->name('team');

        // Employee correction
        Route::post('/corrections', [AttendanceController::class, 'storeCorrection'])->name('corrections.store');

        // Admin / Approver – put named sub-routes before {id}
        Route::get('/records', [AttendanceController::class, 'records'])->name('records');
        Route::get('/qr',      [AttendanceController::class, 'qr'])->name('qr');
        Route::get('/qr/status', [AttendanceController::class, 'qrStatus'])->name('qr.status');
        Route::post('/qr/regenerate', [AttendanceController::class, 'regenerateQr'])->name('qr.regenerate');
        Route::post('/qr/invalidate', [AttendanceController::class, 'invalidateQr'])->name('qr.invalidate');
        Route::post('/qr/display-sessions', [AttendanceController::class, 'storeDisplaySession'])->name('qr.display-sessions.store');
        Route::post('/qr/display-sessions/{id}/revoke', [AttendanceController::class, 'revokeDisplaySession'])->name('qr.display-sessions.revoke');
        Route::get('/settings', [AttendanceController::class, 'settings'])->name('settings');
        Route::put('/settings', [AttendanceController::class, 'updateSettings'])->name('settings.update');

        Route::prefix('corrections')->name('corrections.')->group(function () {
            Route::get('/',             [AttendanceController::class, 'corrections'])->name('index');
            Route::get('/{id}',         [AttendanceController::class, 'showCorrection'])->name('show');
            Route::post('/{id}/review', [AttendanceController::class, 'reviewCorrection'])->name('review');
        });

        // Detail – after all named sub-routes
        Route::get('/{id}', [AttendanceController::class, 'show'])->name('show');
    });

    // ── Leaves ─────────────────────────────────────────────────────────────
    Route::middleware('feature:cuti')->group(function () {
        // Named routes before resource {leave}
        Route::get('leaves/export',      [LeaveController::class, 'export'])->name('leaves.export');
        Route::get('leaves/bulk-export', [LeaveController::class, 'bulkExport'])->name('leaves.bulk-export');

        Route::resource('leaves', LeaveController::class)->parameters(['leaves' => 'leave']);

        Route::get( 'leaves/{leave}/export-pdf', [LeaveController::class, 'exportPdf'])->name('leaves.export-pdf');
        Route::post('leaves/{leave}/approve',    [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('leaves/{leave}/reject',     [LeaveController::class, 'reject'])->name('leaves.reject');
    });

    // Leave Balances & Holidays (permission check inside controller)
    Route::resource('leave-balances', LeaveBalancesController::class);
    Route::get('leave-balances/export', [LeaveBalancesController::class, 'exportLeaveBalances'])->name('leave-balances.export');
    Route::resource('holidays', HolidayController::class);

    // ── Reimbursements ─────────────────────────────────────────────────────
    Route::middleware('feature:reimbursement')->group(function () {
        Route::get('reimbursements/export', [ReimbursementController::class, 'export'])->name('reimbursements.export');
        Route::patch('reimbursements/marked', [ReimbursementController::class, 'markedDone'])->name('reimbursements.marked');

        Route::resource('reimbursements', ReimbursementController::class);

        Route::get( 'reimbursements/{reimbursement}/export-pdf', [ReimbursementController::class, 'exportPdf'])->name('reimbursements.export-pdf');
        Route::post('reimbursements/{reimbursement}/approve',    [ReimbursementController::class, 'approve'])->name('reimbursements.approve');
        Route::post('reimbursements/{reimbursement}/reject',     [ReimbursementController::class, 'reject'])->name('reimbursements.reject');

        Route::resource('reimbursement-types', ReimbursementTypeController::class);
    });

    // ── Overtimes ──────────────────────────────────────────────────────────
    Route::middleware('feature:overtime')->group(function () {
        Route::get('overtimes/export', [OvertimeController::class, 'export'])->name('overtimes.export');
        Route::patch('overtimes/marked', [OvertimeController::class, 'markedDone'])->name('overtimes.marked');

        Route::resource('overtimes', OvertimeController::class);

        Route::get( 'overtimes/{overtime}/export-pdf', [OvertimeController::class, 'exportPdf'])->name('overtimes.export-pdf');
        Route::post('overtimes/{overtime}/approve',    [OvertimeController::class, 'approve'])->name('overtimes.approve');
        Route::post('overtimes/{overtime}/reject',     [OvertimeController::class, 'reject'])->name('overtimes.reject');
    });

    // ── Official Travels ───────────────────────────────────────────────────
    Route::middleware('feature:perjalanan_dinas')->group(function () {
        Route::get('official-travels/export',      [OfficialTravelController::class, 'export'])->name('official-travels.export');
        Route::get('official-travels/bulk-export', [OfficialTravelController::class, 'bulkExport'])->name('official-travels.bulk-export');
        Route::patch('official-travels/marked',    [OfficialTravelController::class, 'markedDone'])->name('official-travels.marked');

        Route::resource('official-travels', OfficialTravelController::class);

        Route::get( 'official-travels/{officialTravel}/export-pdf', [OfficialTravelController::class, 'exportPdf'])->name('official-travels.export-pdf');
        Route::post('official-travels/{officialTravel}/approve',    [OfficialTravelController::class, 'approve'])->name('official-travels.approve');
        Route::post('official-travels/{officialTravel}/reject',     [OfficialTravelController::class, 'reject'])->name('official-travels.reject');
    });

    // ── Admin Management (permission checked inside controllers) ───────────
    Route::resource('divisions', DivisionController::class);

    Route::post('office-locations/resolve-timezone', [OfficeLocationController::class, 'resolveTimezone'])->name('office-locations.resolve-timezone');
    Route::resource('office-locations', OfficeLocationController::class);

    Route::resource('users', UserController::class);

    Route::get( 'settings',                  [CostSettingController::class, 'index'])->name('settings.index');
    Route::get( 'settings/{id}/edit',        [CostSettingController::class, 'edit'])->name('settings.edit');
    Route::put( 'settings/{id}',             [CostSettingController::class, 'update'])->name('settings.update');
    Route::post('settings/update-multiple',  [CostSettingController::class, 'updateMultiple'])->name('settings.update-multiple');
    Route::post('settings/features',         [CostSettingController::class, 'updateFeatures'])->name('settings.features');

    // ── Profile ────────────────────────────────────────────────────────────
    Route::get( 'profile',          [ProfileController::class, 'index'])->name('profile.index');
    Route::put( 'profile',          [ProfileController::class, 'update'])->name('profile.update');
    Route::put( 'profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
