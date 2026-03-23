<?php

use App\Http\Controllers\FinanceController\DashboardController;
use App\Http\Controllers\FinanceController\LeaveController;
use App\Http\Controllers\FinanceController\ReimbursementController;
use App\Http\Controllers\FinanceController\OvertimeController;
use App\Http\Controllers\FinanceController\ProfileController;
use App\Http\Controllers\FinanceController\OfficialTravelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:finance', 'division'])->prefix('approver3')->name('finance.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/', function() {
        return redirect()->route('finance.dashboard');
    });


    // Leave Requests (feature: cuti)
    Route::middleware('feature:cuti')->group(function () {
        Route::get('leaves/{leave}/export-pdf', [LeaveController::class, 'exportPdf'])->name('leaves.exportPdf');
        Route::get('leaves/bulk-export', [LeaveController::class, 'bulkExport'])->name('leaves.bulkExport');
        Route::resource('leaves', LeaveController::class)
            ->parameters([
                "leaves" => "leave"
            ]
        );
    });


    // Reimbursements (feature: reimbursement)
    Route::middleware('feature:reimbursement')->group(function () {
        Route::get('reimbursements/bulk-export', [ReimbursementController::class, 'bulkExport'])->name('reimbursements.bulkExport');
        Route::get('reimbursements/{reimbursement}/export-pdf', [ReimbursementController::class, 'exportPdf'])->name('reimbursements.exportPdf');
        Route::patch('reimbursements/marked', [ReimbursementController::class, 'markedDone'])->name('reimbursements.marked');
        Route::resource('reimbursements', ReimbursementController::class);
    });


    // Overtimes (feature: overtime)
    Route::middleware('feature:overtime')->group(function () {
        Route::get('overtimes/bulk-export', [OvertimeController::class, 'bulkExport'])->name('overtimes.bulkExport');
        Route::get('overtimes/{overtime}/export-pdf', [OvertimeController::class, 'exportPdf'])->name('overtimes.exportPdf');
        Route::patch('overtimes/marked', [OvertimeController::class, 'markedDone'])->name('overtimes.marked');
        Route::resource('overtimes', OvertimeController::class);
    });


    // Official Travels (feature: perjalanan_dinas)
    Route::middleware('feature:perjalanan_dinas')->group(function () {
        Route::get('official-travels/bulk-export', [OfficialTravelController::class, 'bulkExport'])->name('official-travels.bulkExport');
        Route::get('official-travels/{official_travel}/export-pdf', [OfficialTravelController::class, 'exportPdf'])->name('official-travels.exportPdf');
        Route::patch('official-travels/marked', [OfficialTravelController::class, 'markedDone'])->name('official-travels.marked');
        Route::resource('official-travels', OfficialTravelController::class);
    });

    
    // Profile 
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::resource('profile', ProfileController::class);
});
