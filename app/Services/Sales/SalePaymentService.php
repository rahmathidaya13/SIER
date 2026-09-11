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
                'id_user' => $sales->id_user,
                'id_lead' => $sales->id_lead,
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'paid_amount' => $paidAmount,
                'notes_payment' => $notesPayment,
                'payment_at' => now()->toDateTimeString()
            ]);

            $sales->lead->activity()->create([
                'id_lead'     => $sales->lead->id_lead,
                'id_user'     => $sales->id_user,
                'description' => $this->msgPaymentActivity(
                    $paidAmount,
                    $validated['payment_method'],
                    $validated['bank_name'] ?? null,
                    $remaining
                )
            ]);

            return $sales->fresh();
        });
    }
}
