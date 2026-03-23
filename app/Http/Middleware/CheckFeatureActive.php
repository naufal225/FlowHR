<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\FeatureSetting;

class CheckFeatureActive
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        if (!FeatureSetting::isActive($feature)) {
            abort(403, 'Fitur ' . ucfirst(str_replace('_', ' ', $feature)) . ' sedang dinonaktifkan oleh admin.');
        }

        return $next($request);
    }
}
