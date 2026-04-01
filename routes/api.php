<?php

use App\Http\Controllers\Api\Mobile\AttendanceCorrectionController;
use App\Http\Controllers\Api\Mobile\Attendance\AttendanceController;
use App\Http\Controllers\Api\Mobile\Attendance\CheckInController;
use App\Http\Controllers\Api\Mobile\Attendance\CheckOutController;
use App\Http\Controllers\Api\Mobile\MobileAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('mobile')->group(function () {
    Route::post('/auth/login', [MobileAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'mobile.employee'])->group(function () {
        Route::get('/auth/me', [MobileAuthController::class, 'me']);
        Route::post('/auth/logout', [MobileAuthController::class, 'logout']);

        Route::post('/attendance/check-in', CheckInController::class);
        Route::post('/attendance/check-out', CheckOutController::class);
        Route::get('/attendance/today-status', [AttendanceController::class, 'todayStatus']);
        Route::get('/attendance/history', [AttendanceController::class, 'history']);
        Route::get('/attendance/history/{attendanceId}', [AttendanceController::class, 'detail'])
            ->whereNumber('attendanceId');

        Route::get('/attendance/corrections', [AttendanceCorrectionController::class, 'index']);
        Route::post('/attendance/corrections', [AttendanceCorrectionController::class, 'store']);
        Route::get('/attendance/corrections/{correctionId}', [AttendanceCorrectionController::class, 'show'])
            ->whereNumber('correctionId');
    });
});
