<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AdminController\ReimbursementTypeController as AdminReimbursementTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReimbursementTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()->permissions()->canManageSettings(), 403);
            return $next($request);
        });
    }

    public function index(Request $request)  { return app(AdminReimbursementTypeController::class)->index($request); }
    public function create()                 { return app(AdminReimbursementTypeController::class)->create(); }
    public function store(Request $request)  { return app(AdminReimbursementTypeController::class)->store($request); }
    public function show($id)                { return app(AdminReimbursementTypeController::class)->show($id); }
    public function edit($id)                { return app(AdminReimbursementTypeController::class)->edit($id); }
    public function update(Request $request, $id) { return app(AdminReimbursementTypeController::class)->update($request, $id); }
    public function destroy($id)             { return app(AdminReimbursementTypeController::class)->destroy($id); }
}
