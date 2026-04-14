<?php

namespace App\Http\Middleware;

use App\Models\AttendanceQrDisplaySession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAttendanceQrDisplaySession
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionId = (int) $request->route('session');
        $rawKey = trim((string) $request->query('key', ''));

        if ($sessionId <= 0 || $rawKey === '') {
            abort(403, 'Display session signature is invalid.');
        }

        $session = AttendanceQrDisplaySession::query()
            ->with('officeLocation:id,name,timezone')
            ->find($sessionId);

        if ($session === null) {
            abort(404);
        }

        if (! hash_equals($session->token_hash, hash('sha256', $rawKey))) {
            abort(403, 'Display session token is invalid.');
        }

        if (! $session->isActive()) {
            abort(410, 'Display session is no longer active.');
        }

        $touchInterval = max(5, (int) config('attendance.qr_display.touch_interval_seconds', 60));
        $needsTouch = $session->last_seen_at === null
            || $session->last_seen_at->lte(now()->subSeconds($touchInterval));

        if ($needsTouch) {
            $session->forceFill([
                'last_seen_at' => now(),
            ])->save();
        }

        $request->attributes->set('attendance_qr_display_session', $session);

        return $next($request);
    }
}
