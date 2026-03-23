<?php

use App\Http\Controllers\ApproverController\LeaveController;
use App\Http\Controllers\ApproverController\DashboardController;
use App\Http\Controllers\ApproverController\OfficialTravelController;
use App\Http\Controllers\ApproverController\OvertimeController;
use App\Http\Controllers\ApproverController\ProfileController;
use App\Http\Controllers\ApproverController\ReimbursementController;
use App\Models\OfficialTravel;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:approver', 'division'])->prefix('approver')->name('approver.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('approver.dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Leaves (feature: cuti)
    Route::middleware('feature:cuti')->group(function () {
        Route::get('/leaves/export', [LeaveController::class, 'export'])->name('leaves.export');
        Route::get('/leaves/{leave}/export-pdf', [LeaveController::class, 'exportPdf'])->name('leaves.exportPdf');
        Route::patch('/leaves/{leave}/approval', [LeaveController::class, 'approval'])->name('leaves.approval');
        Route::resource('leaves', LeaveController::class)
            ->parameters([
                "leaves" => "leave"
            ]);
    });

    // Official Travels (feature: perjalanan_dinas)
    Route::middleware('feature:perjalanan_dinas')->group(function () {
        Route::get('/official-travels/export', [OfficialTravelController::class, 'export'])->name('official-travels.export');
        Route::get('official-travels/{officialTravel}/export-pdf', [OfficialTravelController::class, 'exportPdf'])->name('official-travels.exportPdf');
        Route::resource('official-travels', OfficialTravelController::class);
        Route::put('/official-travels/{officialTravel}/update-self', [OfficialTravelController::class, 'updateSelf'])->name('official-travels.updateSelf');
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

    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::resource('profile', ProfileController::class);

});
