<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f4f7f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; -webkit-font-smoothing: antialiased;">

    <!-- Wrapper utama -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%"
        style="table-layout: fixed; background-color: #f4f7f6; padding: 40px 20px;">
        <tr>
            <td align="center">

                <!-- Card Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%"
                    style="max-width: 520px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);">

                    <!-- Garis Aksen Atas -->
                    <tr>
                        <td style="height: 6px; background-color: #0d6efd;"></td>
                    </tr>

                    <!-- Konten Email -->
                    <tr>
                        <td style="padding: 40px 40px 30px;">

                            <!-- Header -->
                            <h2
                                style="margin: 0 0 24px; color: #1e293b; font-size: 24px; font-weight: 700; text-align: center;">
                                Verifikasi Email Anda
                            </h2>

                            <!-- Salam -->
                            <p style="margin: 0 0 16px; color: #475569; font-size: 16px; line-height: 1.6;">
                                Halo <strong>{{ ucwords($user->name) }}</strong>,
                            </p>

                            <!-- Pesan Utama -->
                            <p style="margin: 0 0 24px; color: #475569; font-size: 15px; line-height: 1.6;">
                                Terima kasih telah melakukan registrasi. Untuk menyelesaikan proses pembuatan akun,
                                silakan masukkan kode OTP berikut:
                            </p>

                            <!-- Kotak OTP -->
                            <div style="text-align: center; margin: 32px 0;">
                                <div
                                    style="display: inline-block; padding: 18px 40px; background-color: #f0f7ff; border: 2px dashed #0d6efd; border-radius: 12px;">
                                    <span
                                        style="font-size: 36px; font-weight: 800; letter-spacing: 12px; color: #0d6efd; margin-right: -12px;">
                                        {{ $otp }}
                                    </span>
                                </div>
                            </div>

                            <!-- Peringatan & Masa Berlaku -->
                            <p
                                style="margin: 0 0 16px; color: #475569; font-size: 14px; line-height: 1.6; text-align: center;">
                                Kode ini hanya berlaku hingga <strong
                                    style="color: #ef4444;">{{ $expiresAt->format('H:i') }} WIB</strong>.
                            </p>

                            <p
                                style="margin: 0 0 32px; color: #64748b; font-size: 14px; line-height: 1.6; text-align: center; background-color: #f8fafc; padding: 12px; border-radius: 8px;">
                                🔒 <strong>Jaga kerahasiaan akun Anda.</strong> Jangan bagikan kode ini kepada siapa
                                pun, termasuk pihak yang mengatasnamakan kami.
                            </p>

                            <!-- Divider -->
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0 0 24px;">

                            <!-- Footer / Disclaimer -->
                            <p
                                style="margin: 0 0 8px; color: #94a3b8; font-size: 12px; line-height: 1.5; text-align: center;">
                                Jika Anda tidak merasa melakukan pendaftaran, abaikan atau hapus email ini. Seseorang
                                mungkin salah mengetik alamat email mereka.
                            </p>
                            <p
                                style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.5; text-align: center;">
                                &copy; {{ date('Y') }} {{ ucwords(config('app.name')) }}. Email ini dikirim secara
                                otomatis.
                            </p>

                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>

</html>
