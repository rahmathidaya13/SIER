<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserIdleTimeout
{
    private const TIMEOUT_SECONDS  = 3600; // 1 hour in seconds
    public function handle(Request $request, Closure $next): Response
    {

        if (!Auth::guard('web')->check()) {
            return $next($request);
        }

        $lastActivity = $request->session()->get('last_user_activity_at');
        $now = now()->timestamp;


        // Jika timestamp belum ada, inisialisasi
        if (!$lastActivity) {
           return $this->expireSession($request);
        }

        // Periksa batas waktu idle
        if (($now - (int) $lastActivity) >= self::TIMEOUT_SECONDS) {
           return $this->expireSession($request);
        }


        return $next($request);
    }

    private function expireSession(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Endpoint ping harus menerima status 401,
        // bukan redirect HTML yang dianggap berhasil oleh Axios.
        if ($request->routeIs('activity.ping')) {
            return response()->json([
                'message' => 'Sesi telah berakhir. Silakan login kembali.',
            ], 401);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sesi telah berakhir. Silakan login kembali.',
            ], 401);
        }

        return redirect()
            ->route('login')
            ->with(
                'message',
                'Sesi berakhir karena tidak ada aktivitas selama 30 menit.'
            );
    }
}
