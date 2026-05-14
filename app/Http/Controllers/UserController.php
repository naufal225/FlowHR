<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AdminController\UserController as AdminUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()->permissions()->canManageUsers(), 403);
            return $next($request);
        });
    }

    public function index(Request $request)  { return app(AdminUserController::class)->index($request); }
    public function create()                 { return app(AdminUserController::class)->create(); }
    public function store(Request $request)  { return app(AdminUserController::class)->store($request); }
    public function show($id)                { return app(AdminUserController::class)->show($id); }
    public function edit($id)                { return app(AdminUserController::class)->edit($id); }
    public function update(Request $request, $id) { return app(AdminUserController::class)->update($request, $id); }
    public function destroy($id)             { return app(AdminUserController::class)->destroy($id); }
}
