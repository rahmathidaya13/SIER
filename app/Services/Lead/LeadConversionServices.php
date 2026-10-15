<?php

namespace App\Services\Lead;

use App\Models\User;
use App\Models\LeadModel;
use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ImmutableFieldValidator;
use Illuminate\Validation\ValidationException;

class LeadConversionServices
{
    use ImmutableFieldValidator;
    public function convertLeadToSales(LeadModel $leads, array $validated, User $user): SalesModel
    {
        $this->validatePayment($validated);

        return DB::transaction(function () use ($leads, $validated, $user) {

            $fresh = LeadModel::where('id_lead', $leads->id_lead)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fresh->status === 'CLOSED_WON' || $fresh->sales()->exists()) {
                throw ValidationException::withMessages([
                    'error' => 'Lead ini sudah pernah dikonversi.'
                ]);
            }
            $remaining = max(0,  $validated['deal_price'] - $validated['paid_amount']);

            $sales = SalesModel::create([
                'id_lead'        => $fresh->id_lead,
                'id_user'        => $fresh->id_user,
                'id_branch'      => $fresh->id_branch,
                'deal_price'     => $validated['deal_price'],
                'remaining_bill' => $remaining,
                'payment_status' => $validated['payment_status'],
                'paid_amount'    => $validated['paid_amount'],
                'quantity'       => $validated['quantity'],
                'deal_at'        => now()->toDateTimeString(),
                'notes'          => $validated['notes'] ?? null
            ]);

            $idProducts = $fresh->leadProduct()->pluck('id_product')->toArray();
            if (!empty($idProducts)) {
                $rows = array_map(fn($idProduct) => ['id_product' => $idProduct],  $idProducts);
                $sales->salesProduct()->createMany($rows);
            }

            $paymentLabel = $validated['payment_status'] === 'LUNAS'
                ? 'Lunas'
                : 'Dana Awal (DP)';

            $message = "💰 Transaksi {$paymentLabel} dari pelanggan "
                . ucwords($leads->name)
                . ' sebesar Rp ' . number_format($validated['paid_amount'], 0, ',', '.')
                . " ({$validated['payment_status']}).";

            $sales->paymentHistory()->create([
                'id_sales' => $sales->id_sales,
                'id_user' => $user->id,
                'id_lead' => $fresh->id_lead,
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'paid_amount' => $validated['paid_amount'],
                'notes_payment' => $validated['notes_payment'] ?? $message,
                'payment_at' => now()->toDateTimeString()
            ]);


            $fresh->update([
                'status' => 'CLOSED_WON',
                'temperature' => 'HOT',
                'closed_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
                'updated_by' => $user->id,
            ]);

            $fresh->activity()->create([
                'id_user'     =>  $user->id,
                'id_lead'     =>  $fresh->id_lead,
                'source'      => 'transaction',
                'description' => '🎉 Pelanggan DEAL! Transaksi disahkan dengan nilai Rp ' . number_format($validated['deal_price'], 0, ',', '.') . ' (' . $validated['payment_status'] . ').'
            ]);

            return $sales;
        });
    }

    private function validatePayment(array $validated)
    {
        $dealPrice  = $validated['deal_price'] ?? 0;
        $paidAmount = $validated['paid_amount'] ?? 0;

        if (($validated['payment_status'] ?? null) !== 'DP') {
            return;
        }

        $minimalDp = $dealPrice * 0.5;

        if ($paidAmount < $minimalDp) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Uang muka (DP) minimal harus 50% (Minimal: Rp '
                    . number_format($minimalDp, 0, ',', '.') . ')',
            ]);
        }
    }
}
