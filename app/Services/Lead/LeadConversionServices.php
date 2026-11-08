<?php

namespace App\Services\Lead;

use App\Models\User;
use App\Models\LeadModel;
use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadConversionServices
{
    public function convertLeadToSales(LeadModel $leads, array $validated, User $user): SalesModel
    {
        return DB::transaction(function () use ($leads, $validated, $user) {

            $fresh = LeadModel::where('id_lead', $leads->id_lead)
                ->lockForUpdate()
                ->findOrFail($leads->id_lead);

            if ($fresh->status === 'CLOSED_WON' || $fresh->sales()->exists()) {
                throw ValidationException::withMessages([
                    'error' => 'Lead ini sudah pernah dikonversi.'
                ]);
            }
            $remaining = max(0,  $validated['deal_price'] - $validated['paid_amount']);

            $sales = SalesModel::create([
                'id_lead'        => $fresh->id_lead,
                'created_by'        => $user->id,
                'updated_by'        => $user->id,
                'id_branch'      => $fresh->id_branch,
                'id_customer'    => $fresh->id_customer,
                'deal_price'     => $validated['deal_price'],
                'remaining_bill' => $remaining,
                'payment_status' => $validated['payment_status'],
                'paid_amount'    => $validated['paid_amount'],
                'quantity'       => $validated['quantity'],
                'deal_at'        => now()->toDateTimeString(),
                'notes'          => $validated['notes'] ?? null
            ]);

            // Menggunakan pluck terus ke pangkalan data meminimumkan penggunaan memori.
            $idProducts = $fresh->leadProduct()->pluck('id_product')->toArray();
            if (!empty($idProducts)) {
                $rows = array_map(fn($idProduct) => ['id_product' => $idProduct],  $idProducts);
                $sales->salesProduct()->createMany($rows);
            }

            $paymentLabel = $validated['payment_status'] === 'LUNAS'
                ? 'Lunas'
                : 'Dana Awal (DP)';

            $message = "💰 Transaksi {$paymentLabel} dari pelanggan "
                . ucwords($leads->customer->name ?? 'pelanggan')
                . ' sebesar Rp ' . number_format($validated['paid_amount'], 0, ',', '.')
                . " ({$validated['payment_status']}).";

            $sales->paymentHistory()->create([
                'id_sales' => $sales->id_sales,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'id_lead' => $fresh->id_lead,
                'id_customer' => $fresh->id_customer,
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'paid_amount' => $validated['paid_amount'],
                'notes_payment' => $validated['notes_payment'] ?? $message,
                'payment_at' => now()->toDateTimeString()
            ]);


            $fresh->update([
                'updated_by' => $user->id,
                'status' => 'CLOSED_WON',
                'temperature' => 'HOT',
                'closed_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);

            $dealAmountFormatted = number_format($validated['deal_price'], 0, ',', '.');
            $fresh->activity()->create([
                'created_by'     =>  $user->id,
                'updated_by'     =>  $user->id,
                'id_lead'     =>  $fresh->id_lead,
                'source'      => 'transaction',
                'description' => "🎉 Pelanggan DEAL! Transaksi disahkan dengan nilai Rp {$dealAmountFormatted} ({$validated['payment_status']})."
            ]);

            return $sales;
        });
    }
}
