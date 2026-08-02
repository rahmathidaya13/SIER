<?php

namespace App\Services\Lead;

use App\Models\LeadModel;
use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ImmutableFieldValidator;
use Illuminate\Validation\ValidationException;

class LeadConversionServices
{
    use ImmutableFieldValidator;
    public function convertLeadToSales(LeadModel $leads, array $validated): SalesModel
    {
        if ($validated['payment_status'] === 'DP') {
            $minimalDp = $validated['deal_price'] * 0.5; // Hitung 50% dari harga deal
            if ($validated['paid_amount'] < $minimalDp) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Uang muka (DP) minimal harus 50% (Min: Rp ' . number_format($minimalDp, 0, ',', '.') . ')'
                ]);
            }
        }

        return DB::transaction(function () use ($leads, $validated) {
            $remaining = max(0,  $validated['deal_price'] - $validated['paid_amount']);

            $sales = SalesModel::create([
                'id_lead'        => $leads->id_lead,
                'id_user'        => $leads->id_user,
                'id_branch'      => $leads->id_branch,
                'deal_price'     => $validated['deal_price'],
                'remaining_bill' => $remaining,
                'payment_status' => $validated['payment_status'],
                'paid_amount'    => $validated['paid_amount'],
                'quantity'       => $validated['quantity'],
                'deal_at'        => now()->toDateTimeString(),
                'notes'          => $validated['notes']
            ]);

            $idProducts = $leads->leadProduct()->pluck('id_product')->toArray();
            if (!empty($idProducts)) {
                $leadProductMany = array_map(fn($idProduct) => array_merge(['id_product' => $idProduct]),  $idProducts);
                $sales->salesProduct()->createMany($leadProductMany);
            }

            $leads->update([
                'status' => 'CLOSED_WON',
                'temperature' => 'HOT',
                'updated_by' => auth()->id(),
            ]);

            $message = '💰 Transaksi ' . $validated['payment_method'] === 'LUNAS' ? 'lunas' : 'Dana Awal (Dp)' . ' dari pelanggan ' . ucwords($leads->name) . ' sebesar Rp ' . number_format($validated['paid_amount'], 0, ',', '.') . ' (' . $validated['payment_status'] . ').';
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
                $leads->activity()->create([
                    'id_user'     =>  $leads->id_user,
                    'id_lead'     =>  $leads->id_lead,
                    'source'      => 'transaction',
                    'description' => '🎉 Pelanggan DEAL! Transaksi disahkan dengan nilai Rp ' . number_format($validated['deal_price'], 0, ',', '.') . ' (' . $validated['payment_status'] . ').'
                ]);
            }

            return $sales;
        });
    }
}
