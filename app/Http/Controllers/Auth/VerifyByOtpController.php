<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\Otp\OtpService;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class VerifyByOtpController extends Controller
{
    public function showVerifyOtp(Request $request)
    {
        $user = $this->resolveOtpUser($request);

        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('home')->with('message', 'Email sudah diverifikasi');
        }

        return inertia('Auth/VerifyOtp', [
            'email' => $this->maskEmail(
                $user->email
            ),
        ]);
    }

    /**
     * Proses verifikasi OTP.
     */
    public function verifyOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'otp' => [
                'required',
                'digits:6',
            ],
        ], [
            'otp.required' => 'Kode OTP tidak boleh kosong.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 digit angka.',
        ]);

        $user = $this->resolveOtpUser($request);

        if ($user instanceof RedirectResponse) {
            return $user;
        }

        // Verifikasi OTP
        $otpService->verify(
            $user,
            $request->otp
        );

        // Login user & bersihkan session OTP
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(
            'otp_user_id'
        );

        $request->session()->put('last_user_activity_at', now()->timestamp);

        return redirect()
            ->route('home')->with('message', 'Email berhasil diverifikasi. Selamat datang kembali.');
    }

    /**
     * Kirim ulang kode OTP.
     */
    public function resendOtp(Request $request, OtpService $otpService)
    {
        $user = $this->resolveOtpUser($request);

        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $otpService->send($user);

        return back()->with(
            'message',
            'Kode OTP baru telah dikirim ke email Anda.'
        );
    }

    /**
     * Helper privat untuk mengekstrak dan memvalidasi user OTP dari session (DRY).
     */
    private function resolveOtpUser(Request $request): User|RedirectResponse
    {
        $userId = $request->session()->get('otp_user_id');
        if (!$userId) {
            return redirect()->route('register');
        }
        $user = User::find($userId);


        if (!$user) {
            $request->session()->forget('otp_user_id');
            return redirect()->route('register');
        }

        return $user;
    }

    /**
     * Masking email agar aman untuk username panjang maupun pendek.
     */
    private function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email);
        $len = strlen($name);

        // Jika nama email sangat pendek (1-2 huruf), sembunyikan sisanya
        if ($len <= 2) {
            return substr($name, 0, 1) . '*@' . $domain;
        }

        // Tampilkan huruf pertama dan terakhir, sisanya bintang
        $visibleStart = substr($name, 0, 1);
        $visibleEnd = substr($name, -1);
        $maskedLength = max(1, $len - 2);

        return $visibleStart . str_repeat('*', $maskedLength) . $visibleEnd . '@' . $domain;
    }
}
