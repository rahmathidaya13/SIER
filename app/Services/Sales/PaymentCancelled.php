<?php

namespace App\Services\Sales;

use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;

class PaymentCancelled
{
    public function payCancelled(SalesModel $sales, $validated): SalesModel
    {
        return DB::transaction(function () use ($sales, $validated) {
            $sales->update([
                'status' => 'CANCELLED',
                'updated_by' => auth()->id(),
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
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'description' => 'Transaksi dengan invoice '
                    . $sales->invoice . ' dibatalkan. Alasan: '
                    . $this->cancelReasonToString($validated['cancelled_reason'])
                    . '. Catatan: '
                    . $validated['cancelled_notes'],
            ]);

            return $sales;
        });
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
