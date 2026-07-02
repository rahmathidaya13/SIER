<?php

namespace App\Services;

use App\Models\LeadModel;
use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ImmutableFieldValidator;
use Illuminate\Validation\ValidationException;

class LeadConversionServices
{
    use ImmutableFieldValidator;
    public function convertLeadToSales(LeadModel $leads, array $data): SalesModel
    {
       

        if ($data['payment_status'] === 'DP') {
            $minimalDp = $data['deal_price'] * 0.5; // Hitung 50% dari harga deal
            if ($data['paid_amount'] < $minimalDp) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Uang muka (DP) minimal harus 50% (Min: Rp ' . number_format($minimalDp, 0, ',', '.') . ')'
                ]);
            }
        }

        return DB::transaction(function () use ($leads, $data) {
            $remaining = max(0,  $data['deal_price'] - $data['paid_amount']);

            $sales = SalesModel::create([
                'id_lead'        => $leads->id_lead,
                'id_user'        => $leads->id_user,
                'id_product'     => $leads->id_product,
                'id_branch'      => $leads->id_branch,
                'deal_price'     => $data['deal_price'],
                'remaining_bill' => $remaining,
                'paid_amount'    => $data['paid_amount'],
                'payment_status' => $data['payment_status'],
                'payment_method' => $data['payment_method'],
                'deal_at'        => $data['deal_at'],
                'notes'          => $data['notes']
            ]);

            $leads->update([
                'status' => 'CLOSED_WON',
                'temperature' => 'HOT',
                'updated_by' => auth()->id(),
            ]);

            $leads->activity()->create([
                'id_user'     =>  $leads->id_user,
                'id_lead'     =>  $leads->id_lead,
                'description' => '🎉 Pelanggan DEAL! Transaksi disahkan dengan nilai Rp ' . number_format($data['deal_price'], 0, ',', '.') . ' (' . $data['payment_status'] . ').'
            ]);

            return $sales;
        });
    }
}
