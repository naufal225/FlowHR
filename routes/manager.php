<?php

use App\Http\Controllers\ManagerController\LeaveController;
use App\Http\Controllers\ManagerController\OfficialTravelController;
use App\Http\Controllers\ManagerController\OvertimeController;
use App\Http\Controllers\ManagerController\ReimbursementController;
use App\Http\Controllers\ManagerController\DashboardController;
use App\Http\Controllers\ManagerController\ProfileController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth', 'role:manager', 'division'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('manager.dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Leaves (feature: cuti)
    Route::middleware('feature:cuti')->group(function () {
        Route::get('/leaves/export', [LeaveController::class, 'export'])->name('leaves.export');
        Route::put('/leaves/{leave}/update-self', [LeaveController::class, 'updateSelf'])->name('leaves.updateSelf');
        Route::get('/leaves/{leave}/export-pdf', [LeaveController::class, 'exportPdf'])->name('leaves.exportPdf');
        Route::resource('leaves', LeaveController::class)
            ->parameters([
                "leaves" => "leave"
            ]);
    });

    // Reimbursements (feature: reimbursement)
    Route::middleware('feature:reimbursement')->group(function () {
        Route::get('/reimbursements/export', [ReimbursementController::class, 'export'])->name('reimbursements.export');
        Route::get('/reimbursements/{reimbursement}/export-pdf', [ReimbursementController::class, 'exportPdf'])->name('reimbursements.exportPdf');
        Route::put('/reimbursements/{reimbursement}/update-self', [ReimbursementController::class, 'updateSelf'])->name('reimbursements.updateSelf');
        Route::resource('reimbursements', ReimbursementController::class)
            ->parameters([
                "reimbursements" => "reimbursement"
            ]);
    });

    // Overtimes (feature: overtime)
    Route::middleware('feature:overtime')->group(function () {
        Route::get('/overtimes/export', [OvertimeController::class, 'export'])->name('overtimes.export');
        Route::get('overtimes/{overtime}/export-pdf', [OvertimeController::class, 'exportPdf'])->name('overtimes.exportPdf');
        Route::put('/overtimes/{overtime}/update-self', [OvertimeController::class, 'updateSelf'])->name('overtimes.updateSelf');
        Route::resource('overtimes', OvertimeController::class);
    });

    // Official Travels (feature: perjalanan_dinas)
    Route::middleware('feature:perjalanan_dinas')->group(function () {
        Route::get('/official-travels/export', [OfficialTravelController::class, 'export'])->name('official-travels.export');
        Route::put('/official-travels/{officialTravel}/update-self', [OfficialTravelController::class, 'updateSelf'])->name('official-travels.updateSelf');
        Route::get('official-travels/{officialTravel}/export-pdf', [OfficialTravelController::class, 'exportPdf'])->name('official-travels.exportPdf');
        Route::resource('official-travels', OfficialTravelController::class);
    });

    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::resource('profile', ProfileController::class);
});
