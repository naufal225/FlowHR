<?php

namespace App\Http\Middleware;

use App\Enums\Roles;
use App\Exceptions\Attendance\MobileAuthNotAllowedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileEmployeeAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new MobileAuthNotAllowedException();
        }

        if (! $user->is_active || ! $user->hasRole(Roles::Employee->value)) {
            throw new MobileAuthNotAllowedException([
                'user_id' => $user->id,
            ]);
        }

        return $next($request);
    }
}
