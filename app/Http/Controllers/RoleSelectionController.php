<?php

namespace App\Http\Controllers;

use App\Enums\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleSelectionController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $user->load('roles');

        // Filter role yang dimiliki user (kecuali SuperAdmin)
        $userRoles = $user->roles;

        // Jika hanya punya 1 role, langsung redirect
        if ($userRoles->count() === 1) {
            $roleName = $userRoles->first()->name;
            session(['active_role' => $roleName]);
            return app(AuthController::class)->redirectBasedOnRole($roleName);
        }

        $roleLabels = Roles::labels();
        $roleOrder = Roles::selectionOrder();

        // Urutkan roles sesuai urutan yang diinginkan
        $sortedRoles = $userRoles->sortBy(function ($role) use ($roleOrder) {
            $index = array_search($role->name, $roleOrder, true);

            return $index === false ? PHP_INT_MAX : $index;
        });

        return view('auth.choose-role', [
            'roles' => $sortedRoles,
            'roleLabels' => $roleLabels
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'role' => 'required|string'
        ]);

        $user = Auth::user();
        $user->load('roles');

        // Validasi bahwa role yang dipilih memang dimiliki user
        $selectedRole = $user->roles->firstWhere('name', $request->role);

        if (!$selectedRole) {
            return back()->withErrors(['role' => 'Role yang dipilih tidak valid.']);
        }

        // Simpan role aktif ke session
        session(['active_role' => $request->role]);

        return app(AuthController::class)->redirectBasedOnRole($request->role);
    }
}
