<?php

namespace App\Http\Controllers;

use App\Enums\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return $this->redirectToDashboardOrRoleSelection();
        }

        return view('auth.index');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email:dns',
            'password' => 'required|string'
        ], [
            'email.required' => 'Email tidak boleh kosong',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password tidak boleh kosong'
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password anda tidak valid']);
        }

        $request->session()->regenerate();

        return $this->redirectToDashboardOrRoleSelection();
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirect user setelah login:
     * - Jika punya 1 role → langsung ke dashboard
     * - Jika punya >1 role → pilih role
     * - Jika tidak punya role → error
     */
    protected function redirectToDashboardOrRoleSelection()
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

        // Role labels untuk tampilan
        $roleLabels = [
            Roles::Employee->value => 'Employee',
            Roles::Approver->value => 'Approver 1',
            Roles::Manager->value => 'Approver 2',
            Roles::Admin->value => 'Admin',
            Roles::SuperAdmin->value => 'Super Admin',
            Roles::Finance->value => 'Approver 3',
        ];

        // Urutkan roles sesuai urutan yang diinginkan
        $sortedRoles = $userRoles->sortBy(function ($role) use ($roleLabels) {
            $order = array_keys($roleLabels);
            return array_search($role->name, $order);
        });

        return view('auth.choose-role', [
            'roles' => $sortedRoles,
            'roleLabels' => $roleLabels
        ]);
    }

    /**
     * Redirect ke dashboard berdasarkan nama role
     */
    public function redirectBasedOnRole(string $roleName)
    {
        // Pastikan role valid (opsional: cek di enum)
        $validRoles = [
            Roles::SuperAdmin->value,
            Roles::Admin->value,
            Roles::Approver->value,
            Roles::Employee->value,
            Roles::Manager->value,
            Roles::Finance->value,
        ];

        if (!in_array($roleName, $validRoles)) {
            abort(403, 'Role tidak diizinkan.');
        }

        return match ($roleName) {
            Roles::SuperAdmin->value => redirect()->route('super-admin.dashboard'),
            Roles::Admin->value => redirect()->route('admin.dashboard'),
            Roles::Approver->value => redirect()->route('approver.dashboard'),
            Roles::Employee->value => redirect()->route('employee.dashboard'),
            Roles::Manager->value => redirect()->route('manager.dashboard'),
            Roles::Finance->value => redirect()->route('finance.dashboard'),
            default => abort(403),
        };
    }
}
