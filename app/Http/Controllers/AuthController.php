<?php

namespace App\Http\Controllers;

use App\Enums\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectToDashboardOrRoleSelection();
        }

        $request->session()->regenerateToken();

        return response()
            ->view('auth.index')
            ->withHeaders($this->authNoCacheHeaders());
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
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

        return redirect()->route("dashboard");
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda telah logout. Silakan login kembali.')
            ->withHeaders($this->authNoCacheHeaders());
    }

    /**
     * Redirect user setelah login ke dashboard berdasarkan role dengan prioritas tertinggi.
     * Tidak lagi meminta user memilih role – semua role ditampilkan sekaligus di view.
     */
    protected function redirectToDashboardOrRoleSelection()
    {
        $user = Auth::user();
        $user->load('roles');

        $userRoleNames = $user->roles->pluck('name')->toArray();

        if (empty($userRoleNames)) {
            abort(403, 'User tidak memiliki role yang valid.');
        }

        // Pilih role dengan prioritas tertinggi dari role yang dimiliki user
        $roleOrder = Roles::selectionOrder();
        $highestRole = collect($roleOrder)->first(fn($r) => in_array($r, $userRoleNames));

        if (!$highestRole) {
            abort(403, 'User tidak memiliki role yang valid.');
        }

        session(['active_role' => $highestRole]);
        return $this->redirectBasedOnRole($highestRole);
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

    /**
     * Hindari form login lama dari cache browser yang bisa memicu mismatch CSRF.
     *
     * @return array<string, string>
     */
    private function authNoCacheHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Fri, 01 Jan 1990 00:00:00 GMT',
        ];
    }
}
