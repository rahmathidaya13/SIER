<?php

namespace App\Services\Lead;

use App\Models\LeadModel;
use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ImmutableFieldValidator;
use App\Traits\ActivityMessageFormatter;
use App\Services\Authorization\LeadAuthorizationService;
use Illuminate\Support\Facades\Log;

class LeadServices
{
    use ImmutableFieldValidator, ActivityMessageFormatter;

    protected $leadConversionServices;
    protected $authorization;
    public function __construct(LeadConversionServices $leadConversionServices, LeadAuthorizationService $authorization)
    {
        $this->leadConversionServices = $leadConversionServices;
        $this->authorization = $authorization;
    }

    public function store(array $validated, $user): LeadModel
    {
        DB::beginTransaction();
        try {

            $leads = LeadModel::create([
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'id_branch' => $user->id_branch,
                'id_channel' => $validated['id_channel'],
                'id_customer' => $validated['id_customer'],
                'status' => $validated['status'],
                'temperature' => $validated['temperature'],
                'notes' => $validated['notes'],
                'closed_at' => $validated['status'] === 'CLOSED_WON' ? now()->toDateTimeString() : null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ]);

            // masukan banyak product kedalam database
            $this->syncLeadProducts($leads, $validated['id_product'] ?? []);

            // Jika prospek langsung Deal/Closing
            $this->handleClosingTransition($leads, $validated, $user);

            // Jika SEMUA TAHAP di atas berhasil tanpa error, sahkan datanya (Commit)
            DB::commit();
            return $leads;
        } catch (\Exception $error) {

            DB::rollBack(); // JIKA ADA ERROR (Server mati, database putus, dll), BATALKAN SEMUA!
            Log::error("Gagal menyimpan Lead baru: " . $error); // Catat error ke file laravel.log agar mudah di-debug
            throw $error; // Lemparkan error kembali ke controller agar bisa ditangkap oleh sistem validasi Vue
        }
    }

    public function update(LeadModel $leads, array $validated, $user): LeadModel
    {
        DB::beginTransaction();
        try {

            $this->__validate($leads, $validated, [
                'status' => [
                    'locked_value' => 'CLOSED_WON',
                    'message' => 'Tidak dapat mengubah status prospek yang sudah disahkan menjadi status lain.',
                    'error_key' => 'status'
                ],
                'temperature' => [
                    'locked_value' => 'HOT',
                    'message' => 'Tidak dapat mengubah suhu prospek yang sudah HOT menjadi suhu lain.',
                    'error_key' => 'temperature'
                ]
            ]);

            $leads->update([
                'id_customer' => $validated['id_customer'],
                'id_channel' => $validated['id_channel'],
                'updated_by' => $user->id !== $leads->created_by ? $user->id : $leads->created_by,
                'status' => $validated['status'],
                'temperature' => $validated['temperature'],
                'notes' => $validated['notes'],
                'closed_at' => $validated['status'] === 'CLOSED_WON' ? now()->toDateTimeString() : null,
                'updated_at' => now()->toDateTimeString(),
            ]);


            if (array_key_exists('id_product', $validated)) {
                $this->syncLeadProducts($leads, (array) $validated['id_product']);
            }
            DB::commit();
            return $leads;
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal memperbarui Lead: " . $error);
            throw $error;
        }
    }
    public function delete(LeadModel $leads, $user)
    {
        DB::beginTransaction();
        try {
            $leads->leadProduct()->delete();
            $leads->activity()->delete();
            $leads->delete();
            DB::commit();
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal menghapus Lead {$leads->customer->name}: " . $error);
            throw $error;
        }
    }

    private function handleClosingTransition(LeadModel $leads, array $validated, $user)
    {
        $isClosing = ($validated['status'] ?? null) === 'CLOSED_WON'
            && ($validated['temperature'] ?? null) === 'HOT';

        if (!$isClosing) {
            return;
        }
        if ($leads->sales()->exists()) {
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
    private function syncLeadProducts(LeadModel $leads, array $productIds)
    {
        $leads->leadProduct()->delete();
        $productIds = array_filter($productIds, fn($id) => $id !== null && $id !== '');

        if ($productIds === []) {
            return;
        }

        $rows = array_map(fn($id) => ['id_product' => $id], $productIds);
        $leads->leadProduct()->createMany($rows);
    }
    private function convertLeadToSales(LeadModel $leads, array $validated)
    {
        $remaining = max(0,  $validated['deal_price'] - $validated['paid_amount']);

        $sales = SalesModel::create([
            'id_lead'        => $leads->id_lead,
            'id_customer'    => $leads->id_customer,
            'created_by'        => $leads->created_by,
            'updated_by'        => $leads->updated_by,
            'id_branch'      => $leads->id_branch,
            'deal_price'     => $validated['deal_price'],
            'paid_amount'    => $validated['paid_amount'],
            'remaining_bill' => $remaining,
            'payment_status' => $validated['payment_status'],
            'quantity'       => $validated['quantity'],
            'deal_at'        => now()->toDateTimeString(),
            'notes'          => $validated['notes_sales'],
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
            'updated_by' => auth()->id() !== $leads->created_by ? auth()->id() : $leads->created_by,
        ]);

        return $sales;
    }
}
