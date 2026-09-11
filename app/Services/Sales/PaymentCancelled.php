<?php

namespace App\Services\Sales;

use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentCancelled
{
    public function __invoke(SalesModel $sales, $validated): SalesModel
    {
        DB::beginTransaction();
        try {
            if ($sales->status === 'CANCELLED') {
                throw ValidationException::withMessages([
                    'error' => 'Transaksi ini sudah dibatalkan sebelumnya.',
                ]);
            }
            $sales->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancelled_reason' => $validated['cancelled_reason'],
                'cancelled_notes' => $validated['cancelled_notes'] ?? null,
            ]);
            $sales->lead->update([
                'status' => 'CLOSED_LOST',
            ]);
            $sales->lead->activity()->create([
                'id_user' => auth()->id(),
                'source' => 'updated',
                'description' => 'Transaksi dengan invoice '
                    . $sales->invoice . ' dibatalkan. Alasan: '
                    . $this->cancelReasonToString($validated['cancelled_reason'])
                    . '. Catatan: '
                    . $validated['cancelled_notes'],
            ]);
            DB::commit();
            return $sales->fresh();
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal membatalkan transaksi: " . $error);
            throw $error;
        }
    }
    private function cancelReasonToString($reason): string
    {
        return match ($reason) {
            'CUSTOMER_CANCELLED' => 'Pelanggan membatalkan pesanan',
            'PAYMENT_FAILED' => 'Pembayaran tidak diselesaikan',
            'ORDER_UNFULFILLABLE' => 'Pesanan tidak dapat dipenuhi',
            'DATA_ERROR' => 'Kesalahan input transaksi',
            'DUPLICATE' => 'Duplikasi transaksi',
            'OTHER' => 'Lainnya',
            default => 'Alasan tidak diketahui',
        };
    }
}
