<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerificationController extends Controller
{
    public function notice()
    {
        return inertia('Auth/Verify');
    }

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return redirect()->route('home')->with('message', 'Email berhasil diverifikasi');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home')->with('message', 'Email sudah diverifikasi');
        }

        $request->user()->sendEmailVerificationNotification();
        return redirect()->back()->with('message', 'Tautan verifikasi baru telah dikirim ke email Anda.');
    }
}
