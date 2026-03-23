<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureHasDivision
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && is_null($user->division_id)) {
            abort(403, 'Anda belum punya divisi, tunggu admin menempatkan Anda di suatu divisi.');
        }

        return $next($request);
    }
}

