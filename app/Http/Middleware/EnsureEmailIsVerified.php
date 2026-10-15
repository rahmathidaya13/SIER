<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->routeIs('verification.otp', 'verification.otp.verify', 'verification.otp.resend')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Alamat email Anda belum diverifikasi.',
            ], 403);
        }

        // Web → arahkan ke form OTP
        return redirect()->route('verification.otp');
    }
}
