<?php

use App\Http\Controllers\Api\Mobile\Attendance\AttendanceController;
use App\Http\Controllers\Api\Mobile\Attendance\CheckInController;
use App\Http\Controllers\Api\Mobile\Attendance\CheckOutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->prefix('mobile')->group(function () {
    Route::post('/attendance/check-in', CheckInController::class);
    Route::post('/attendance/check-out', CheckOutController::class);
    Route::get('/attendance/today-status', [AttendanceController::class, 'todayStatus']);
});
