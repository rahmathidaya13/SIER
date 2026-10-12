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
        if ($request->user() && !$request->user()->hasVerifiedEmail()) {
            // Jika request berasal dari API / Axios / Fetch yang mengharapkan JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Alamat email Anda belum diverifikasi.'
                ], 403);
            }

            // Jika request dari Web / Inertia, arahkan ke form OTP
            return redirect()->route('verification.otp');
        }

        return $next($request);
    }
}
