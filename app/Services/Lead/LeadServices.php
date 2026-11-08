<?php

namespace App\Services\Lead;

use App\Models\LeadModel;
use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ActivityMessageFormatter;

class LeadServices
{
    use  ActivityMessageFormatter;
    public function store(array $validated, $user): LeadModel
    {
        return DB::transaction(function () use ($validated, $user) {
            $leads = LeadModel::create([
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'id_branch' => $user->id_branch,
                'id_customer' => $validated['id_customer'],
                'id_channel' => $validated['id_channel'],
                'temperature' => $validated['temperature'],
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'closed_at' => $validated['status'] === 'CLOSED_WON' ? now()->toDateTimeString() : null,
            ]);

            if (!empty($validated['id_product'])) {
                $this->syncLeadProducts($leads, (array) $validated['id_product']);
            }

            $this->handleClosingTransition($leads, $validated, $user);

            return $leads;
        });
    }

    public function update(LeadModel $leads, array $validated, $user): LeadModel
    {
        return DB::transaction(function () use ($leads, $validated, $user) {
            $leads->update([
                'id_customer' => $validated['id_customer'],
                'id_channel' => $validated['id_channel'],
                'updated_by' => $user->id,
                'status' => $validated['status'],
                'temperature' => $validated['temperature'],
                'notes' => $validated['notes'],
                'closed_at' => $validated['status'] === 'CLOSED_WON' ? now()->toDateTimeString() : null,
                'updated_at' => now()->toDateTimeString()
            ]);
            if (array_key_exists('id_product', $validated)) {
                $this->syncLeadProducts($leads, (array) $validated['id_product']);
            }
            $this->handleClosingTransition($leads, $validated, $user);
            return $leads;
        });
    }
    public function delete(LeadModel $leads, $user)
    {
        DB::transaction(function () use ($leads, $user) {
            $leads->leadProduct()->delete();
            $leads->activity()->delete();
            $leads->delete();
        });
    }

    private function handleClosingTransition(LeadModel $leads, array $validated, $user)
    {
        // Kondisi untuk closing: status harus 'CLOSED_WON' dan temperature harus 'HOT'
        $isClosing = ($validated['status'] ?? null) === 'CLOSED_WON'
            && ($validated['temperature'] ?? null) === 'HOT';

        // Jika tidak memenuhi syarat closing atau data sales sudah ada, hentikan
        if (!$isClosing || $leads->sales()->exists()) {
            return;
        }

        $sales = $this->convertLeadToSales($leads, $validated);

        $sales->paymentHistory()->create([
            'id_sales'       => $sales->id_sales,
            'id_customer'    => $sales->id_customer,
            'created_by'        => $user->id,
            'updated_by'        => $user->id,
            'id_lead'        => $leads->id_lead,
            'payment_method' => $validated['payment_method'],
            'bank_name'      => $validated['bank_name'] ?? null,
            'paid_amount'    => $validated['paid_amount'],
            'notes_payment'  => 'Transaksi awal pada saat pengesahan closing / deal',
            'payment_at'     => now()->toDateTimeString()
        ]);
        $leads->activity()->create([
            'created_by'     => $user->id,
            'updated_by'     => $user->id,
            'id_lead'     => $leads->id_lead,
            'description' => $this->msgClosingDeal($validated)
        ]);
    }

    /**
     * Menyinkronkan daftar produk dengan Lead
     */
    private function syncLeadProducts(LeadModel $leads, array $productIds)
    {
        // Menghapus semua produk yang ada terkait lead ini (cara sederhana untuk update)
        $productIds = array_filter($productIds, fn($id) => $id !== null && $id !== '');
        $leads->leadProduct()->delete();

        if (!empty($productIds)) {
            $rows = array_map(fn($id) => ['id_product' => $id], $productIds);
            $leads->leadProduct()->createMany($rows);
        }

        if ($productIds === []) {
            return;
        }
    }

    /**
     * Mengonversi record Lead menjadi Sales yang baru
     */
    private function convertLeadToSales(LeadModel $leads, array $validated)
    {
        // Menghitung sisa tagihan (memastikan tidak negatif)
        $remaining = max(0,  $validated['deal_price'] - $validated['paid_amount']);

        $sales = SalesModel::create([
            'id_lead'        => $leads->id_lead,
            'created_by'        => $leads->created_by,
            'updated_by'        => auth()->id() ?? $leads->updated_by,
            'id_customer'    => $leads->id_customer,
            'id_branch'      => $leads->id_branch,
            'deal_price'     => $validated['deal_price'],
            'paid_amount'    => $validated['paid_amount'],
            'remaining_bill' => $remaining,
            'payment_status' => $validated['payment_status'],
            'quantity'       => $validated['quantity'],
            'deal_at'        => now()->toDateTimeString(),
            'notes'          => $validated['notes_sales'] ?? null,
        ]);

        // masukan banyak product kedalam database
        if (!empty($validated['id_product'])) {
            $rows = array_map(fn($idProduct) => ['id_product' => $idProduct], $validated['id_product']);
            $sales->salesProduct()->createMany($rows);
        }

        $leads->update([
            'status' => 'CLOSED_WON',
            'temperature' => 'HOT',
            'closed_at' => now()->toDateTimeString(),
            'updated_by' => auth()->id()
        ]);

        return $sales;
    }
}
