<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AdminController\CostSettingController as AdminCostSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CostSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()->permissions()->canManageSettings(), 403);
            return $next($request);
        });
    }

    public function index(Request $request)  { return app(AdminCostSettingController::class)->index($request); }
    public function edit($id)                { return app(AdminCostSettingController::class)->edit($id); }
    public function update(Request $request, $id) { return app(AdminCostSettingController::class)->update($request, $id); }
    public function updateMultiple(Request $request) { return app(AdminCostSettingController::class)->updateMultiple($request); }
    public function updateFeatures(Request $request) { return app(AdminCostSettingController::class)->updateFeatures($request); }
}
