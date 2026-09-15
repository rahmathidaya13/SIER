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
                'id_user' => $user->id,
                'id_branch' => $user->id_branch,
                'id_channel' => $validated['id_channel'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => $validated['status'],
                'temperature' => $validated['temperature'],
                'customer_type' => $validated['customer_type'],
                'notes' => $validated['notes'],
                'closed_at' => $validated['status'] === 'CLOSED_WON' ? now()->toDateTimeString() : null
            ]);

            // masukan banyak product kedalam database
            if (!empty($validated['id_product'])) {
                $leadProductMany = array_map(fn($idProduct) => array_merge(['id_product' => $idProduct]), $validated['id_product']);
                $leads->leadProduct()->createMany($leadProductMany);
            }

            // Jika prospek langsung Deal/Closing
            if ($validated['status'] === 'CLOSED_WON' && $validated['temperature'] === 'HOT') {

                $sales = $this->convertLeadToSales($leads, $validated);

                $sales->paymentHistory()->create([
                    'id_sales'       => $sales->id_sales,
                    'id_user'        => $user->id,
                    'id_lead'        => $leads->id_lead,
                    'payment_method' => $validated['payment_method'],
                    'bank_name'      => $validated['bank_name'] ?? null,
                    'paid_amount'    => $validated['paid_amount'],
                    'notes_payment'  => 'Transaksi awal pada saat pengesahan closing / deal',
                    'payment_at'     => now()->toDateTimeString()
                ]);

                $leads->activity()->create([
                    'id_user'     => $user->id,
                    'id_lead'     => $leads->id_lead,
                    'description' => $this->msgClosingDeal($validated)
                ]);
            }
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
                'id_channel' => $validated['id_channel'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'customer_type' => $validated['customer_type'],
                'status' => $validated['status'],
                'temperature' => $validated['temperature'],
                'notes' => $validated['notes'],
                'closed_at' => $validated['status'] === 'CLOSED_WON' ? now()->toDateTimeString() : null
            ]);

            if (isset($validated['id_product']) && is_array($validated['id_product'])) {
                $leads->leadProduct()->delete();
                $leadProductMany = array_map(fn($idProduct) => array_merge(['id_product' => $idProduct]), $validated['id_product']);
                if (!empty($leadProductMany)) {
                    $leads->leadProduct()->createMany($leadProductMany);
                }
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
            $leads->delete();
            DB::commit();
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal menghapus Lead: " . $error);
            throw $error;
        }
    }
    private function convertLeadToSales(LeadModel $leads, array $validated)
    {
        return DB::transaction(function () use ($leads, $validated) {
            $remaining = max(0,  $validated['deal_price'] - $validated['paid_amount']);

            $sales = SalesModel::create([
                'id_lead'        => $leads->id_lead,
                'id_user'        => $leads->id_user,
                'id_branch'      => $leads->id_branch,
                'deal_price'     => $validated['deal_price'],
                'paid_amount'    => $validated['paid_amount'],
                'remaining_bill' => $remaining,
                'payment_status' => $validated['payment_status'],
                'quantity'       => $validated['quantity'],
                'deal_at'        => now()->toDateTimeString(),
                'notes'          => $validated['notes_sales']
            ]);

            // masukan banyak product kedalam database
            if (!empty($validated['id_product'])) {
                $salesProductMany = array_map(fn($idProduct) => array_merge(['id_product' => $idProduct]), $validated['id_product']);
                $sales->salesProduct()->createMany($salesProductMany);
            }

            $leads->update([
                'status' => 'CLOSED_WON',
                'temperature' => 'HOT',
                'closed_at' => now()->toDateTimeString(),
                'updated_by' => auth()->id(),
            ]);

            return $sales;
        });
    }
}
