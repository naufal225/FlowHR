<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AdminController\HolidayController as AdminHolidayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()->permissions()->canManageHolidays(), 403);
            return $next($request);
        });
    }

    public function index(Request $request)  { return app(AdminHolidayController::class)->index($request); }
    public function create()                 { return app(AdminHolidayController::class)->create(); }
    public function store(Request $request)  { return app(AdminHolidayController::class)->store($request); }
    public function show($id)                { return app(AdminHolidayController::class)->show($id); }
    public function edit($id)                { return app(AdminHolidayController::class)->edit($id); }
    public function update(Request $request, $id) { return app(AdminHolidayController::class)->update($request, $id); }
    public function destroy($id)             { return app(AdminHolidayController::class)->destroy($id); }
}
