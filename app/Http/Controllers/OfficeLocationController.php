<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AdminController\OfficeLocationController as AdminOfficeLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficeLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()->permissions()->canManageDivisions(), 403);
            return $next($request);
        });
    }

    public function index(Request $request)  { return app(AdminOfficeLocationController::class)->index($request); }
    public function create()                 { return app(AdminOfficeLocationController::class)->create(); }
    public function store(Request $request)  { return app(AdminOfficeLocationController::class)->store($request); }
    public function show($id)                { return app(AdminOfficeLocationController::class)->show($id); }
    public function edit($id)                { return app(AdminOfficeLocationController::class)->edit($id); }
    public function update(Request $request, $id) { return app(AdminOfficeLocationController::class)->update($request, $id); }
    public function destroy($id)             { return app(AdminOfficeLocationController::class)->destroy($id); }
    public function resolveTimezone(Request $request) { return app(AdminOfficeLocationController::class)->resolveTimezone($request); }
}
