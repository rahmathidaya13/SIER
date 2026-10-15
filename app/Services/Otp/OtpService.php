<?php

namespace App\Services\Otp;

use App\Models\User;
use App\Mail\OtpVerificationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OtpService
{
    /**
     * OTP lifetime in minutes.
     */
    protected int $otpLifetime = 5;

    /**
     * Resend cooldown in seconds.
     */
    protected int $resendCooldown = 60;

    /**
     * Maximum verification attempts.
     */
    protected int $maxAttempts = 5;



    public function send(User $user)
    {
        $latestOtp = $user->otpCodes()->latest()->first();
        // Cek cooldown
        if ($latestOtp && $latestOtp->created_at->gt(now()->subSeconds($this->resendCooldown))) {
            $remaining = (int) ceil(
                $this->resendCooldown - $latestOtp->created_at->diffInSeconds(now())
            );

            throw ValidationException::withMessages([
                'otp' => "Silakan tunggu {$remaining} detik sebelum meminta OTP baru.",
            ]);
        }


        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes($this->otpLifetime);

        // Bungkus database & email dalam transaksi
        DB::transaction(function () use ($user, $otp, $expiresAt) {
            // Hapus OTP lama yang belum terverifikasi
            $user->otpCodes()->whereNull('verified_at')->delete();

            $user->otpCodes()->create([
                'otp_code'   => Hash::make($otp),
                'attempts'   => 0,
                'expires_at' => $expiresAt,
            ]);

            // Kirim email (jika gagal, DB::transaction akan rollback pembuatan OTP)
            Mail::to($user->email)->send(
                new OtpVerificationMail($user, $otp, $expiresAt)
            );
        });

        return true;
    }


    /**
     * Validasi kode OTP.
     */
    public function verify(User $user, string $code): bool
    {
        $recordOtp = $user->otpCodes()
            ->whereNull('verified_at')
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$recordOtp) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        // Cek limit percobaan
        if ($recordOtp->attempts >= $this->maxAttempts) {
            throw ValidationException::withMessages([
                'otp' => 'Terlalu banyak percobaan. Silakan minta OTP baru.',
            ]);
        }

        // Cek kecocokan kode hash
        if (!Hash::check($code, $recordOtp->otp_code)) {
            // Increment nilai attempts di DB secara atomic
            $recordOtp->increment('attempts');

            // Ambil nilai terbaru setelah di-increment
            $currentAttempts = $recordOtp->fresh()->attempts;
            $remaining = max(0, $this->maxAttempts - $currentAttempts);

            throw ValidationException::withMessages([
                'otp' => "Kode OTP salah. Sisa percobaan: {$remaining}.",
            ]);
        }

        // Tandai OTP telah sukses diverifikasi
        $recordOtp->update([
            'verified_at' => now(),
        ]);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }
        return true;
    }

    /**
     * Cek apakah user masih punya OTP aktif (belum verified & belum expired).
     */
    public function hasActiveOtp(User $user): bool
    {
        return $user->otpCodes()
            ->whereNull('verified_at')
            ->where('expires_at', '>=', now())
            ->exists();
    }
    public function getOtpLifetime(): int
    {
        return $this->otpLifetime;
    }

    public function getResendCooldown(): int
    {
        return $this->resendCooldown;
    }
}
