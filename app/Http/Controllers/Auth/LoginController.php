<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
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

        if (!$request->user()->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice');
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

        // $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, true)) {
            RateLimiter::hit($this->throttleKey($request));
            throw ValidationException::withMessages([
                'error' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
    }
    public function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 3)) {
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
        return Str::transliterate(Str::lower($request->string('email')) . '|' . $request->ip());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Hapus sesi & regenerasi token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with(' message', 'Anda berhasil logout');
    }
}
