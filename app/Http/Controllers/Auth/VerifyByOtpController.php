<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class VerifyByOtpController extends Controller
{
    public function __construct()
    {
        $this->middleware('web');
    }
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

        // Kalau sudah verified (misal double-submit), langsung login & pulang.
        if ($user->hasVerifiedEmail()) {
            return $this->loginAndRedirect($request, $user);
        }

        try {
            // Verifikasi OTP
            $otpService->verify(
                $user,
                $request->otp
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('OTP verify gagal', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        $user->refresh();

        if (!$user->hasVerifiedEmail()) {
            // Fallback: tandai verified, karena OTP sudah divalidasi.
            $user->forceFill(['email_verified_at' => now()])->save();

            event(new Verified($user));

            Log::warning('OtpService tidak menandai email verified, fallback dipakai', [
                'user_id' => $user->id,
            ]);
        }

        return $this->loginAndRedirect($request, $user);
    }

    private function loginAndRedirect(Request $request, User $user): RedirectResponse
    {
        $request->session()->regenerate();

        Auth::login($user,  false);
        $request->session()->forget('otp_user_id');
        $request->session()->put('last_user_activity_at', now()->timestamp);

        return redirect()
            ->route('home')
            ->with('message', 'Email berhasil diverifikasi');
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
        // 1) Dari session OTP (alur normal setelah register).
        $userId = $request->session()->get('otp_user_id');

        if ($userId) {
            $user = User::find($userId);

            if ($user) {
                return $user;
            }

            // User ID di session tidak valid → bersihkan.
            $request->session()->forget('otp_user_id');
        }

        // 2) Fallback: user sudah login (misal redirect loop dari middleware).
        $authUser = $request->user();

        if ($authUser) {
            return $authUser;
        }

        // Benar-benar tidak ada user → lempar ke login (bukan register,
        // supaya user yang sudah punya akun tapi logout tidak bingung).
        return redirect()
            ->route('login')
            ->with('message', 'Sesi verifikasi berakhir. Silakan login kembali.');
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
