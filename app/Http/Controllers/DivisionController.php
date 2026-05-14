<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AdminController\DivisionController as AdminDivisionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DivisionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()->permissions()->canManageDivisions(), 403);
            return $next($request);
        });
    }

    public function index(Request $request)  { return app(AdminDivisionController::class)->index($request); }
    public function create()                 { return app(AdminDivisionController::class)->create(); }
    public function store(Request $request)  { return app(AdminDivisionController::class)->store($request); }
    public function show($id)                { return app(AdminDivisionController::class)->show($id); }
    public function edit($id)                { return app(AdminDivisionController::class)->edit($id); }
    public function update(Request $request, $id) { return app(AdminDivisionController::class)->update($request, $id); }
    public function destroy($id)             { return app(AdminDivisionController::class)->destroy($id); }
}
