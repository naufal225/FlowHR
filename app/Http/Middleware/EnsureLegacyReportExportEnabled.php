<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLegacyReportExportEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((bool) config('reporting.legacy_export_enabled', false)) {
            return $next($request);
        }

        abort(404);
    }
}

