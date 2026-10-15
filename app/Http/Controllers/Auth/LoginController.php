<?php

namespace App\Http\Controllers\Auth;

use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 3;
    private const LOCKOUT_DECAY_SECONDS = 60;
    public function __construct(private readonly OtpService $otpService) {}
    public function login()
    {
        return inertia('Auth/Login');
    }
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|max:50',
        ], [
            'email.required' => 'Email tidak boleh kosong.',
            'email.email' => 'Format email tidak sesuai.',
            'email.exists' => 'Email tidak terdaftar di sistem.',

            'password.required' => 'Password tidak boleh kosong',
            'password.string' => 'Password harus berupa karakter yang valid.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.max' => 'Password maksimal 50 karakter.',
        ]);

        $this->authenticate($request);

        $request->session()->regenerate();

        $request->session()->put(
            'last_user_activity_at',
            now()->timestamp
        );

        $user = Auth::user();

        if (!$request->user()->hasVerifiedEmail()) {
            return $this->handleUnverifiedUser($request, $user);
        }

        return redirect(RouteServiceProvider::HOME)->with('message', 'Login berhasil. Selamat datang kembali.');
    }
    public function authenticate(Request $request): void
    {
        $this->ensureIsNotRateLimited($request);

        $credentials = $request->only([
            'email',
            'password'
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey($request), self::LOCKOUT_DECAY_SECONDS);
            throw ValidationException::withMessages([
                'error' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
    }
    public function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_LOGIN_ATTEMPTS)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));
        throw ValidationException::withMessages([
            'email' => [
                "Kredensial ini tidak sesuai dengan data yang ada
                Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ],
        ]);
    }
    public function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->string('email')) . '|' . $request->ip()
        );
    }
    private function handleUnverifiedUser($request, $user)
    {
        $userId = $user->id;

        // Logout dulu, tapi JANGAN pakai invalidate() — kita butuh session untuk otp_user_id.
        Auth::guard('web')->logout();

        // Regenerate session ID untuk keamanan (data tetap terjaga).
        $request->session()->regenerate();

        $request->session()->put('otp_user_id', $userId);

        // ✅ Kirim OTP hanya kalau belum ada OTP aktif (hindari cooldown trap).
        $otpSent = false;

        if (!$this->otpService->hasActiveOtp($user)) {
            try {
                $this->otpService->send($user);
                $otpSent = true;
            } catch (ValidationException $e) {
                // Kalau cooldown, abaikan — user masih bisa pakai OTP lama.
                if (!str_contains($e->getMessage(), 'Silakan tunggu')) {
                    throw $e;
                }
            }
        }

        $message = $otpSent
            ? 'Kode OTP baru telah dikirim ke email Anda untuk verifikasi.'
            : 'Silakan masukkan kode OTP yang telah dikirim ke email Anda.';

        return redirect()
            ->route('verification.otp')
            ->with('message', $message);
    }
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Hapus sesi & regenerasi token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::clearHistory();
        return redirect()->route('login')->with(' message', 'Anda berhasil logout');
    }
}
