<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function showForgotPasswordForm()
    {
        return inertia('Auth/Passwords/Email');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email|max:50|min:5',
        ], [
            'email.required' => 'Email tidak boleh kosong.',
            'email.email' => 'Format email tidak sesuai.',
            'email.exists' => 'Email tidak terdaftar di sistem.',
            'email.max' => 'Email maksimal 50 karakter.',
            'email.min' => 'Email minimal 5 karakter.',
        ]);

        $status = Password::sendResetLink($validated);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'error' => __($status),
            ]);
        }
        return redirect()->back()->with('message', 'Link pengaturan ulang kata sandi telah dikirim ke email Anda.');
    }
}
