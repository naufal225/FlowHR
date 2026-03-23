<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // 1. Pastikan user sudah login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 2. Ambil role aktif dari session
        $activeRole = session('active_role');

        // 3. Jika belum memilih role (misal: akses langsung ke dashboard)
        if (!$activeRole) {
            // Redirect ke halaman pilih role
            return redirect()->route('choose-role');
        }

        // 4. Cek apakah role aktif termasuk dalam daftar yang diizinkan
        if (!in_array($activeRole, $roles)) {
            abort(403, 'Akses ditolak. Role tidak memiliki izin.');
        }

        return $next($request);
    }
}
