<?php

namespace App\Services\Sales;

use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;

class SalePaymentService
{
    public function payRemaining(SalesModel $sales, $validated): SalesModel
    {
        return DB::transaction(function () use ($sales, $validated) {

            $paidAmount = $validated['paid_amount'];
            $remaining = max(0, $sales->remaining_bill - $paidAmount);

            $sales->update([
                'paid_amount'      => $sales->paid_amount + $paidAmount,
                'remaining_bill'   => $remaining,
                'payment_status'   => $remaining == 0 ? 'LUNAS' : 'DP',
            ]);

            $message = 'Pelunasan dari sisa tagihan pelanggan ' . ucwords($sales->lead->name) . ' berhasil dilakukan. Diterima dana sebesar Rp ' . number_format($paidAmount, 0, ',', '.');

            $sales->paymentHistory()->create([
                'id_sales' => $sales->id_sales,
                'id_user' => $sales->id_user,
                'id_lead' => $sales->id_lead,
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'paid_amount' => $validated['paid_amount'],
                'notes_payment' => $validated['notes_payment'] ?? $message,
                'payment_at' => now()->toDateTimeString()
            ]);

            if ($sales->lead) {
                $sales->lead->activity()->create([
                    'id_lead'     => $sales->lead->id_lead,
                    'id_user'     => $sales->id_user,
                    'description' => '💰 PELUNASAN VIA [ ' . strtoupper($validated['payment_method']) . ' ' . strtoupper($validated['bank_name']) . ' ]: Diterima dana sebesar Rp ' . number_format($paidAmount, 0, ',', '.') . '. Sisa tagihan saat ini: Rp ' . number_format($sales->remaining_bill, 0, ',', '.')
                ]);
            }

            return $sales->fresh();
        });
    }
}
