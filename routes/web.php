<?php

use App\Events\SendMessage;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\RoleSelectionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Enums\Roles;

// ==============================
// TESTING EVENTS
// ==============================

Route::get('/_test-bc', function () {
    $leave = \App\Models\Leave::latest()->first() ?? \App\Models\Leave::factory()->create();
    event(new \App\Events\LeaveSubmitted($leave, 1)); // 👈 harus 1
    return 'sent';
})->middleware('auth');

Route::get('/send-message-test', function () {
    event(new SendMessage(['apa' => 1212121]));
});

// ==============================
// PUBLIC APPROVAL & PASSWORD RESET
// ==============================
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/approve/{token}', [\App\Http\Controllers\PublicApprovalController::class, 'show'])
        ->name('public.approval.show'); // view 2 tombol

    Route::post('/approve/{token}', [\App\Http\Controllers\PublicApprovalController::class, 'act'])
        ->name('public.approval.act'); // eksekusi approve/reject
});

Route::middleware('guest')->group(function () {
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
    Route::get('/password-reset-success', [ResetPasswordController::class, 'success'])->name('password.reset.success');
});

// ==============================
// ROOT REDIRECT
// ==============================
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $role = session('active_role'); // ambil role aktif dari session

    return match ($role) {
        Roles::SuperAdmin->value => redirect()->route('super-admin.dashboard'),
        Roles::Admin->value      => redirect()->route('admin.dashboard'),
        Roles::Approver->value   => redirect()->route('approver.dashboard'),
        Roles::Employee->value   => redirect()->route('employee.dashboard'),
        Roles::Manager->value    => redirect()->route('manager.dashboard'),
        Roles::Finance->value    => redirect()->route('finance.dashboard'),
        default                  => abort(403, 'Role tidak diizinkan.'),
    };
});

// ==============================
// AUTH ROUTES
// ==============================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/choose-role', [RoleSelectionController::class, 'show'])->name('choose-role');
    Route::post('/choose-role', [RoleSelectionController::class, 'store'])->name('choose-role.store');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ==============================
// MODULE ROUTES
// ==============================
require __DIR__.'/super-admin.php';
require __DIR__.'/admin.php';
require __DIR__.'/approver.php';
require __DIR__.'/employee.php';
require __DIR__.'/manager.php';
require __DIR__.'/finance.php';
require __DIR__.'/channels.php';

// test
