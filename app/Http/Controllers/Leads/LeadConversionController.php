<?php

namespace App\Http\Controllers\Leads;

use Exception;
use App\Models\LeadModel;
use App\Models\SalesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use App\Http\Controllers\Leads\LeadsRepository;
use App\Traits\LeadConversionValidation;

class LeadConversionController extends Controller
{
    use BranchMutationHelper, LeadConversionValidation;
    public function store(Request $request, LeadModel $leads)
    {
        $this->validateLeadConversion($request->all());

        if ($leads->status === 'CLOSED_WON') {
            return redirect()->back()->with('error', 'Prospek ini sudah pernah disahkan menjadi transaksi!');
        }

        if ($request['payment_status'] === 'DP') {
            $minimalDp = $request['deal_price'] * 0.5; // Hitung 50% dari harga deal

            if ($request['paid_amount'] < $minimalDp) {
                return redirect()->back()->withErrors([
                    'paid_amount' => 'Uang muka (DP) minimal harus 50% (Min: Rp ' . number_format($minimalDp, 0, ',', '.') . ')'
                ])->withInput();
            }
        }
        // hitung sisa hutang / tagihan
        $remaining = (int) $request['deal_price'] - $request['paid_amount'];

        try {
            DB::transaction(function () use ($request, $leads, $remaining) {
                SalesModel::create([
                    'id_lead'        => $leads->id_lead,
                    'id_user'        => $leads->id_user,
                    'id_product'     => $leads->id_product,
                    'id_branch'      => $leads->id_branch,
                    'deal_price'     => $request['deal_price'],
                    'remaining_bill' => max(0, $remaining),
                    'paid_amount'    => $request['paid_amount'],
                    'payment_status' => $request['payment_status'],
                    'payment_method' => $request['payment_method'],
                    'deal_at'   => $request['deal_at'],
                    'notes'          => $request['notes']
                ]);
                $leads->update([
                    'status' => 'CLOSED_WON',
                    'temperature' => 'HOT',
                    'updated_by' => auth()->id(),
                ]);
                $leads->activity()->create([
                    'id_user'     =>  $leads->id_user,
                    'id_lead'     =>  $leads->id_lead,
                    'description' => '🎉 Pelanggan DEAL! Transaksi disahkan dengan nilai Rp ' . number_format($request['deal_price'], 0, ',', '.') . ' (' . $request['payment_status'] . ').'
                ]);
            });

            $user = auth()->user();
            $this->invalidateCaches(LeadsRepository::class, ['global', $user->id_branch]);
            return redirect()->back()->with('success', 'Transaksi atas nama ' . $leads->name . ' sudah berhasil closing.');
        } catch (Exception $err) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data transaksi: ' . $err->getMessage());
        }
    }
}
