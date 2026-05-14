<?php

namespace App\Http\Controllers;

use App\Enums\Roles;
use App\Http\Controllers\Attendance\AdminAttendanceController;
use App\Http\Controllers\EmployeeController\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\ApproverController\AttendanceController as ApproverAttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Unified AttendanceController – delegates to existing role-specific controllers
 * during the transition period. This avoids duplicating complex business logic.
 */
class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        return app(EmployeeAttendanceController::class)->index($request);
    }

    public function history(Request $request)
    {
        return app(EmployeeAttendanceController::class)->history($request);
    }

    public function show(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value])) {
            return app(AdminAttendanceController::class)->show($request, $id);
        }
        return app(EmployeeAttendanceController::class)->show($request, $id);
    }

    public function storeCorrection(Request $request)
    {
        return app(EmployeeAttendanceController::class)->storeCorrection($request);
    }

    // Team attendance overview for approver
    public function team(Request $request)
    {
        abort_unless(Auth::user()->hasRole([Roles::Approver->value, Roles::Admin->value, Roles::SuperAdmin->value]), 403);
        return app(ApproverAttendanceController::class)->index($request);
    }

    // Admin-only methods
    public function records(Request $request)
    {
        abort_unless(Auth::user()->hasRole([Roles::Admin->value, Roles::SuperAdmin->value]), 403);
        return app(AdminAttendanceController::class)->records($request);
    }

    public function corrections(Request $request)
    {
        $user = Auth::user();
        if ($user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value])) {
            return app(AdminAttendanceController::class)->corrections($request);
        }
        abort_unless($user->hasRole(Roles::Approver->value), 403);
        return app(ApproverAttendanceController::class)->corrections($request);
    }

    public function showCorrection(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value])) {
            return app(AdminAttendanceController::class)->showCorrection($request, $id);
        }
        abort_unless($user->hasRole(Roles::Approver->value), 403);
        return app(ApproverAttendanceController::class)->showCorrection($request, $id);
    }

    public function reviewCorrection(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value])) {
            return app(AdminAttendanceController::class)->reviewCorrection($request, $id);
        }
        abort_unless($user->hasRole(Roles::Approver->value), 403);
        return app(ApproverAttendanceController::class)->reviewCorrection($request, $id);
    }

    public function qr(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canManageAttendanceSettings(), 403);
        return app(AdminAttendanceController::class)->qr($request);
    }

    public function qrStatus(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canManageAttendanceSettings(), 403);
        return app(AdminAttendanceController::class)->qrStatus($request);
    }

    public function regenerateQr(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canManageAttendanceSettings(), 403);
        return app(AdminAttendanceController::class)->regenerateQr($request);
    }

    public function invalidateQr(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canManageAttendanceSettings(), 403);
        return app(AdminAttendanceController::class)->invalidateQr($request);
    }

    public function storeDisplaySession(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canManageAttendanceSettings(), 403);
        return app(AdminAttendanceController::class)->storeDisplaySession($request);
    }

    public function revokeDisplaySession(Request $request, $id)
    {
        abort_unless(Auth::user()->permissions()->canManageAttendanceSettings(), 403);
        return app(AdminAttendanceController::class)->revokeDisplaySession($request, $id);
    }

    public function settings(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canManageAttendanceSettings(), 403);
        return app(AdminAttendanceController::class)->settings($request);
    }

    public function updateSettings(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canManageAttendanceSettings(), 403);
        return app(AdminAttendanceController::class)->updateSettings($request);
    }
}
