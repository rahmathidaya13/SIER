<?php

namespace App\Traits;

trait ActivityMessageFormatter
{
    /**
     * Helper internal untuk format Rupiah.
     */
    private function formatRupiah($amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }
    /**
     * Log 1: Saat Lead Baru Didaftarkan
     * Contoh: "✨ PROSPEK BARU: Budi Susanto telah didaftarkan ke sistem dengan status AWAL dan suhu WARM."
     */
    public function msgLeadCreated(array $data): string
    {
        $name   = strtoupper($data['name'] ?? 'Konsumen');
        $status = strtoupper($data['status'] ?? 'Unknown');
        $temp   = strtoupper($data['temperature'] ?? 'Unknown');

        return "✨ PROSPEK BARU: {$name} telah didaftarkan ke dalam sistem. Status awal ditetapkan sebagai [{$status}] dengan indikator suhu [{$temp}].";
    }
    /**
     * Log 2: Saat Terjadi Pengesahan Transaksi (Closing Deal)
     * Contoh: "🎉 TRANSAKSI DISAHKAN! Total Kesepakatan: Rp 10.000.000. Pembayaran awal menggunakan metode TRANSFER sebesar Rp 5.000.000."
     */
    public function msgClosingDeal(array $data): string
    {
        $dealPrice   = $this->formatRupiah($data['deal_price'] ?? 0);
        $paidAmount  = $this->formatRupiah($data['paid_amount'] ?? 0);
        $method      = strtoupper($data['payment_method'] ?? 'cash');
        $bankName      = strtoupper($data['bank_name'] ?? null);
        $paymentStatus = strtoupper($data['payment_status'] ?? 'Unknown');

        return "🎉 TRANSAKSI DISAHKAN! Total nilai kesepakatan (Deal Price) adalah {$dealPrice}. Pelanggan telah melakukan setoran awal sebesar {$paidAmount} menggunakan metode [ {$method} {$bankName}] dengan status [{$paymentStatus}].";
    }

    /**
     * Log 3: Saat Data Profil/Status Diperbarui
     * Contoh: "🔄 PEMBARUAN DATA: Status prospek diubah menjadi FOLLOW_UP dengan suhu HOT."
     */
    public function msgLeadUpdated(array $data): string
    {
        $status = match ($data['status'] ?? 'Unknown') {
            'NEW' => 'Baru Masuk',
            'FOLLOW_UP' => 'Sedang Follow Up',
            'CLOSED_WON' => 'Closing / Terjual',
            'CLOSED_LOST' => 'Closing / Tidak Terjual',
            default => 'Unknown',
        };

        $temp = match ($data['temperature'] ?? 'Unknown') {
            'WARM' => 'Warm (Tanya-tanya)',
            'COLD' => 'Hot (Sekedar Lewat)',
            default => 'Unknown',
        };

        return "PEMBARUAN DATA: Informasi profil prospek telah diperbarui. Status saat ini berada pada tahap [{$status}] dengan suhu [{$temp}]";
    }

    /**
     * Log 4: Saat Pelunasan Tagihan (Untuk ditaruh di SaleController nanti)
     * Contoh: "💰 PEMBAYARAN MASUK: Diterima dana sebesar Rp 5.000.000 via QRIS."
     */
    public function msgPaymentReceived(string $amount, array $data, string $remaining): string
    {
        $paid      = $this->formatRupiah($amount);
        $sisa      = $this->formatRupiah($remaining);
        $payMethod = strtoupper($data['payment_method'] ?? 'Unknown');
        $bankName  = strtoupper($data['bank_name'] ?? 'Unknown');

        return "💰 PEMBAYARAN MASUK: Diterima dana sebesar {$paid} melalui metode [ {$payMethod}{-$bankName} ]. Sisa tagihan sistem saat ini menunjukkan {$sisa}.";
    }

    /**
     * Log 5: Catatan Internal untuk Kuitansi (Tabel Payment History)
     */
    public function msgPaymentNotes(string $name, $amount, string $method, ?string $bank, string $status): string
    {
        $paid       = $this->formatRupiah($amount);
        $methodBank = strtoupper(trim($method . ' ' . $bank));
        $customer   = ucwords($name);

        return "Penerimaan dana lanjutan untuk pelanggan { $customer } sebesar { $paid } menggunakan metode { $methodBank }. Status tagihan saat ini: {$status}.";
    }

    /**
     * Log 6: Timeline Aktivitas Pelunasan/Cicilan (Tabel Lead Activity)
     */
    public function msgPaymentActivity($amount, string $method, ?string $bank, $remaining): string
    {
        $paid       = $this->formatRupiah($amount);
        $sisa       = $this->formatRupiah($remaining);
        $methodBank = strtoupper(trim($method . ' ' . $bank));

        // Jika sisa tagihan 0, tampilkan pesan LUNAS PENUH dengan piala
        if ($remaining <= 0) {
            return "💰 PELUNASAN PENUH: Diterima pembayaran akhir sebesar {$paid} via [{$methodBank}]. Seluruh tagihan konsumen telah LUNAS sepenuhnya! 🏆";
        }

        // Jika masih ada sisa, tampilkan pesan CICILAN
        return "💰 PEMBAYARAN TAGIHAN: Diterima dana sebesar {$paid} via [{$methodBank}]. Sisa tagihan sistem saat ini menunjukkan {$sisa}.";
    }
}
