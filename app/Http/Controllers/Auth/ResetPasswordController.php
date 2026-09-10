<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function showResetPasswordForm(Request $request, string $token)
    {
        return inertia('Auth/Passwords/Reset', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email|max:50|min:5',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'token.required' => 'Token perbarui kata sandi tidak valid.',
            'email.required' => 'Email tidak boleh kosong.',
            'email.email' => 'Format email tidak sesuai.',
            'email.exists' => 'Email tidak terdaftar di sistem.',
            'email.max' => 'Email maksimal 50 karakter.',
            'email.min' => 'Email minimal 5 karakter.',
            'password.required' => 'Kata sandi tidak boleh kosong.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai.',
        ]);

        $status = Password::reset(
            $validated,
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->save();
                $user->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'error' => $this->translatePasswordStatus($status),
            ]);
        }
        return redirect()->route('login')->with('message', 'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru Anda.');
    }

    private function translatePasswordStatus(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN =>
            'Link reset password tidak valid atau sudah kedaluwarsa.',

            Password::INVALID_USER =>
            'Data akun tidak ditemukan.',

            Password::RESET_THROTTLED =>
            'Silakan tunggu beberapa saat sebelum mencoba kembali.',

            default =>
            __($status),
        };
    }
}
