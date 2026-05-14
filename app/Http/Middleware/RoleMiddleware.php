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
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $user->loadMissing('roles');

        $userRoles = $user->roles->pluck('name')->toArray();

        if (empty(array_intersect($roles, $userRoles))) {
            abort(403, 'Akses ditolak. Role tidak memiliki izin.');
        }

        return $next($request);
    }
}
