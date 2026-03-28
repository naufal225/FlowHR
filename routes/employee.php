<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController\DashboardController;
use App\Http\Controllers\EmployeeController\LeaveController;
use App\Http\Controllers\EmployeeController\ReimbursementController;
use App\Http\Controllers\EmployeeController\OvertimeController;
use App\Http\Controllers\EmployeeController\OfficialTravelController;
use App\Http\Controllers\EmployeeController\ProfileController;
use App\Http\Controllers\EmployeeController\AttendanceController;

Route::middleware(['auth', 'role:employee', 'division'])->prefix('employee')->name('employee.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/', function () {
        return redirect()->route('employee.dashboard');
    });


    // Leave Requests (feature: cuti)
    Route::middleware('feature:cuti')->group(function () {
        Route::get('leaves/{leave}/export-pdf', [LeaveController::class, 'exportPdf'])->name('leaves.exportPdf');
        Route::resource('leaves', LeaveController::class)
            ->parameters([
                "leaves" => "leave"
            ]);
    });


    // Reimbursements (feature: reimbursement)
    Route::middleware('feature:reimbursement')->group(function () {
        Route::get('reimbursements/{reimbursement}/export-pdf', [ReimbursementController::class, 'exportPdf'])->name('reimbursements.exportPdf');
        Route::resource('reimbursements', ReimbursementController::class);
    });


    // Overtimes (feature: overtime)
    Route::middleware('feature:overtime')->group(function () {
        Route::get('overtimes/{overtime}/export-pdf', [OvertimeController::class, 'exportPdf'])->name('overtimes.exportPdf');
        Route::resource('overtimes', OvertimeController::class);
    });


    // Official Travels (feature: perjalanan_dinas)
    Route::middleware('feature:perjalanan_dinas')->group(function () {
        Route::get('official-travels/{official_travel}/export-pdf', [OfficialTravelController::class, 'exportPdf'])->name('official-travels.exportPdf');
        Route::resource('official-travels', OfficialTravelController::class);
    });

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/history', [AttendanceController::class, 'history'])->name('history');
        Route::get('/{attendance}', [AttendanceController::class, 'show'])->whereNumber('attendance')->name('show');
        Route::post('/corrections', [AttendanceController::class, 'storeCorrection'])->name('corrections.store');
    });


    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::resource('profile', ProfileController::class);
});
