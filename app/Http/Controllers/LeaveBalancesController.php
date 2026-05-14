<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AdminController\LeaveBalancesController as AdminLeaveBalancesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveBalancesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()->permissions()->canManageLeaveBalances(), 403);
            return $next($request);
        });
    }

    public function index(Request $request)  { return app(AdminLeaveBalancesController::class)->index($request); }
    public function show($id)                { return app(AdminLeaveBalancesController::class)->show($id); }
    public function edit($id)                { return app(AdminLeaveBalancesController::class)->edit($id); }
    public function update(Request $request, $id) { return app(AdminLeaveBalancesController::class)->update($request, $id); }
    public function create()                 { return app(AdminLeaveBalancesController::class)->create(); }
    public function store(Request $request)  { return app(AdminLeaveBalancesController::class)->store($request); }
    public function destroy($id)             { return app(AdminLeaveBalancesController::class)->destroy($id); }
    public function exportLeaveBalances(Request $request) { return app(AdminLeaveBalancesController::class)->exportLeaveBalances($request); }
}
