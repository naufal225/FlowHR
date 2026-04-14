<?php

use App\Enums\Roles;
use App\Http\Controllers\AdminController\ApproverController;
use App\Http\Controllers\AdminController\CostSettingController;
use App\Http\Controllers\AdminController\CustomerController;
use App\Http\Controllers\AdminController\DashboardController;
use App\Http\Controllers\AdminController\DivisionController;
use App\Http\Controllers\AdminController\HolidayController;
use App\Http\Controllers\AdminController\LeaveBalancesController;
use App\Http\Controllers\AdminController\OfficeLocationController;
use App\Http\Controllers\AdminController\UserController;
use App\Http\Controllers\AdminController\LeaveController;
use App\Http\Controllers\AdminController\OfficialTravelController;
use App\Http\Controllers\AdminController\OvertimeController;
use App\Http\Controllers\AdminController\ProfileController;
use App\Http\Controllers\AdminController\ReimbursementController;
use App\Http\Controllers\AdminController\ReimbursementTypeController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\Attendance\AdminAttendanceController as AttendanceController;
use App\Models\OfficialTravel;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('divisions', DivisionController::class);

    Route::resource('office-locations', OfficeLocationController::class)->only([
        'index',
        'show',
        'create',
        'store',
        'edit',
        'update',
        'destroy',
    ]);
    Route::post('office-locations/resolve-timezone', [OfficeLocationController::class, 'resolveTimezone'])
        ->name('office-locations.resolve-timezone');

    Route::resource('users', UserController::class);

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/records', [AttendanceController::class, 'records'])->name('records');
        Route::get('/corrections', [AttendanceController::class, 'corrections'])->name('corrections.index');
        Route::get('/corrections/{correction}', [AttendanceController::class, 'showCorrection'])->whereNumber('correction')->name('corrections.show');
        Route::post('/corrections/{correction}/review', [AttendanceController::class, 'reviewCorrection'])->whereNumber('correction')->name('corrections.review');
        Route::get('/qr', [AttendanceController::class, 'qr'])->name('qr');
        Route::get('/qr/status', [AttendanceController::class, 'qrStatus'])->name('qr.status');
        Route::post('/qr/regenerate', [AttendanceController::class, 'regenerateQr'])->name('qr.regenerate');
        Route::post('/qr/invalidate', [AttendanceController::class, 'invalidateQr'])->name('qr.invalidate');
        Route::post('/qr/display-sessions', [AttendanceController::class, 'storeDisplaySession'])->name('qr.display-sessions.store');
        Route::post('/qr/display-sessions/{displaySession}/revoke', [AttendanceController::class, 'revokeDisplaySession'])
            ->whereNumber('displaySession')
            ->name('qr.display-sessions.revoke');
        Route::get('/settings', [AttendanceController::class, 'settings'])->name('settings');
        Route::put('/settings', [AttendanceController::class, 'updateSettings'])->name('settings.update');
        Route::get('/{attendance}', [AttendanceController::class, 'show'])->whereNumber('attendance')->name('show');
    });

    Route::get('/leaves/export', [LeaveController::class, 'export'])
        ->name('leaves.export');

    Route::get('leaves/{leave}/export-pdf', [LeaveController::class, 'exportPdf'])->name('leaves.exportPdf');
    Route::resource('leaves', LeaveController::class)
        ->parameters([
            "leaves" => "leave"
        ]);

    Route::get('/leave-balances/export', [LeaveBalancesController::class, 'exportLeaveBalances'])->name('leave-balances.export');
    Route::resource('leave-balances', LeaveBalancesController::class);

    Route::get('/reimbursements/export', [ReimbursementController::class, 'export'])
        ->name('reimbursements.export');
    Route::get('reimbursements/{reimbursement}/export-pdf', [ReimbursementController::class, 'exportPdf'])->name('reimbursements.exportPdf');
    Route::resource('reimbursements', ReimbursementController::class)
        ->parameters([
            "reimbursements" => "reimbursement"
        ]);

    Route::resource('reimbursement-types', ReimbursementTypeController::class)
        ->parameters([
            "reimbursementType" => "reimbursementType"
        ]);

    Route::get('/overtimes/export', [OvertimeController::class, 'export'])
        ->name('overtimes.export');
    Route::get('overtimes/{overtime}/export-pdf', [OvertimeController::class, 'exportPdf'])->name('overtimes.exportPdf');
    Route::resource('overtimes', OvertimeController::class);

    Route::get('/official-travels/export', [OfficialTravelController::class, 'export'])
        ->name('official-travels.export');
    Route::get('official-travels/{officialTravel}/export-pdf', [OfficialTravelController::class, 'exportPdf'])->name('official-travels.exportPdf');
    Route::resource('official-travels', OfficialTravelController::class);

    Route::controller(ReportExportController::class)
        ->prefix('report-exports')
        ->name('report-exports.')
        ->group(function () {
            Route::get('/', 'index')->defaults('role_scope', 'admin')->name('index');
            Route::post('/', 'store')->defaults('role_scope', 'admin')->name('store');
            Route::get('{reportExport}', 'show')->defaults('role_scope', 'admin')->name('show');
            Route::get('{reportExport}/download', 'download')->defaults('role_scope', 'admin')->name('download');
        });

    Route::resource('holidays', HolidayController::class);

    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::resource('profile', ProfileController::class);

    // Cost Settings Routes
    Route::get('/settings', [CostSettingController::class, 'index'])->name('settings.index');
    Route::get('/settings/{costSetting}/edit', [CostSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/{costSetting}', [CostSettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/update-multiple', [CostSettingController::class, 'updateMultiple'])->name('settings.update-multiple');
    Route::post('/settings/features', [CostSettingController::class, 'updateFeatures'])->name('settings.features.update');

});

