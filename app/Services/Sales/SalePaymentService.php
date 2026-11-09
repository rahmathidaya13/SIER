<?php

namespace App\Services\Sales;

use App\Models\SalesModel;
use App\Traits\ActivityMessageFormatter;
use Illuminate\Support\Facades\DB;

class SalePaymentService
{
    use ActivityMessageFormatter;
    public function payRemaining(SalesModel $sales, $validated): SalesModel
    {
        return DB::transaction(function () use ($sales, $validated) {

            $paidAmount = $validated['paid_amount'];
            $remaining = max(0, $sales->remaining_bill - $paidAmount);
            $newStatus  = $remaining == 0 ? 'LUNAS' : 'DP';

            $sales->update([
                'updated_by'       => auth()->user()->id,
                'paid_amount'      => $sales->paid_amount + $paidAmount,
                'remaining_bill'   => $remaining,
                'payment_status'   => $remaining == 0 ? 'LUNAS' : 'DP',
            ]);

            $notesPayment = $validated['notes_payment'] ?? $this->msgPaymentNotes(
                $sales->lead->name ?? 'Unknown',
                $paidAmount,
                $validated['payment_method'],
                $validated['bank_name'] ?? null,
                $newStatus
            );

            $sales->paymentHistory()->create([
                'id_sales' => $sales->id_sales,
                'created_by' => $sales->created_by,
                'updated_by' => $sales->updated_by,
                'id_customer' => $sales->id_customer,
                'id_lead' => $sales->id_lead,
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'paid_amount' => $paidAmount,
                'notes_payment' => $notesPayment,
                'payment_at' => now()->toDateTimeString()
            ]);

            $sales->lead->activity()->create([
                'id_lead'     => $sales->lead->id_lead,
                'created_by'     => $sales->created_by,
                'description' => $this->msgPaymentActivity(
                    $paidAmount,
                    $validated['payment_method'],
                    $validated['bank_name'] ?? null,
                    $remaining
                )
            ]);

            return $sales;
        });
    }

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
