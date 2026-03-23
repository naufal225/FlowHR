<?php

use App\Http\Controllers\SuperAdminController\CostSettingController;
use App\Http\Controllers\SuperAdminController\HolidayController;
use App\Http\Controllers\SuperAdminController\ApproverController;
use App\Http\Controllers\SuperAdminController\CustomerController;
use App\Http\Controllers\SuperAdminController\DashboardController;
use App\Http\Controllers\SuperAdminController\DivisionController;
use App\Http\Controllers\SuperAdminController\LeaveBalancesController;
use App\Http\Controllers\SuperAdminController\ReimbursementTypeController;
use App\Http\Controllers\SuperAdminController\UserController;
use App\Http\Controllers\SuperAdminController\LeaveController;
use App\Http\Controllers\SuperAdminController\OfficialTravelController;
use App\Http\Controllers\SuperAdminController\OvertimeController;
use App\Http\Controllers\SuperAdminController\ProfileController;
use App\Http\Controllers\SuperAdminController\ReimbursementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:superAdmin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('super-admin.dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('divisions', DivisionController::class);

    Route::resource('users', UserController::class);

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
    Route::get('/reimbursements/export/pdf/all', [ReimbursementController::class, 'exportPdfAllData'])
        ->name('reimbursements.export.pdf.all');
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
    Route::get('/overtimes/export/pdf/all', [OvertimeController::class, 'exportPdfAllData'])
        ->name('overtimes.export.pdf.all');
    Route::resource('overtimes', OvertimeController::class);

    Route::get('/official-travels/export', [OfficialTravelController::class, 'export'])
        ->name('official-travels.export');
    Route::get('official-travels/{officialTravel}/export-pdf', [OfficialTravelController::class, 'exportPdf'])->name('official-travels.exportPdf');
    Route::get('/official-travels/export/pdf/all', [OfficialTravelController::class, 'exportPdfAllData'])
        ->name('official-travels.export.pdf.all');
    Route::resource('official-travels', OfficialTravelController::class);


    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::resource('profile', ProfileController::class);


    Route::resource('holidays', HolidayController::class);

    // Cost Settings Routes
    Route::get('/settings', [CostSettingController::class, 'index'])->name('settings.index');
    Route::get('/settings/{costSetting}/edit', [CostSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/{costSetting}', [CostSettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/update-multiple', [CostSettingController::class, 'updateMultiple'])->name('settings.update-multiple');
    Route::post('/settings/features', [CostSettingController::class, 'updateFeatures'])->name('settings.features.update');

});
