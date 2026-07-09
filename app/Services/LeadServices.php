<?php

namespace App\Services;

use App\Models\LeadModel;
use App\Models\SalesModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ImmutableFieldValidator;
use App\Services\LeadConversionServices;
use App\Services\Authorization\LeadAuthorizationService;
use App\Traits\ActivityMessageFormatter;

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
        return DB::transaction(function () use ($validated, $user) {

            $leads = LeadModel::create([
                'id_user' => $user->id,
                'id_branch' => $user->id_branch,
                'id_channel' => $validated['id_channel'],
                'id_product' => $validated['id_product'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => $validated['status'],
                'temperature' => $validated['temperature'],
                'follow_up_date' => $validated['follow_up_date'],
                'notes' => $validated['notes'],
            ]);

            $activityDesc = $validated['new_activity'] ?? $this->msgLeadCreated($validated);

            if (!empty($validated['new_activity'])) {
                $leads->activity()->create([
                    'id_user'     => $user->id,
                    'id_lead'     => $leads->id_lead,
                    'description' => $activityDesc
                ]);
            }

            if ($validated['status'] === 'CLOSED_WON' && $validated['temperature'] === 'HOT') {

                $sales = $this->convertLeadToSales($leads, $validated);

                $sales->paymentHistory()->create([
                    'id_sales' => $sales->id_sales,
                    'id_user' => $user->id,
                    'id_lead' => $leads->id_lead,
                    'payment_method' => $validated['payment_method'],
                    'bank_name' => $validated['bank_name'] ?? null,
                    'paid_amount' => $validated['paid_amount'],
                    'notes_payment' => 'Transaksi awal pada saat pengesahan closing / deal',
                    'payment_at' => now()->toDateTimeString()
                ]);

                $leads->activity()->create([
                    'id_user'     => $user->id,
                    'id_lead'     => $leads->id_lead,
                    'description' => $this->msgClosingDeal($validated)
                ]);
            }

            return $leads;
        });
    }

    public function update(string $id, array $data, $user): LeadModel
    {
        return DB::transaction(function () use ($id, $data, $user) {
            $leads = LeadModel::findOrFail($id);

            // Validasi untuk memastikan bahwa status dan temperature tidak diubah jika sudah memiliki nilai tertentu
            $this->__validate($leads, $data, [
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
                'id_channel' => $data['id_channel'],
                'id_product' => $data['id_product'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'status' => $data['status'],
                'follow_up_date' => $data['follow_up_date'],
                'temperature' => $data['temperature'],
                'notes' => $data['notes'],
            ]);

            if (!empty($data['new_activity'])) {
                $leads->activity()->create([
                    'id_user' => $user->id,
                    'id_lead'     => $leads->id_lead,
                    'description' => $data['new_activity']
                ]);
                $leads->touch();
            }
            return $leads;
        });
    }
    public function delete(LeadModel $leads)
    {
        $user = auth()->user();

        $this->authorization->ensureCanDelete($user, $leads);

        $leads->delete();
    }

    private function convertLeadToSales(LeadModel $leads, array $validated)
    {
        return DB::transaction(function () use ($leads, $validated) {
            $remaining = max(0,  $validated['deal_price'] - $validated['paid_amount']);

            $sales = SalesModel::create([
                'id_lead'        => $leads->id_lead,
                'id_user'        => $leads->id_user,
                'id_product'     => $leads->id_product,
                'id_branch'      => $leads->id_branch,
                'deal_price'     => $validated['deal_price'],
                'paid_amount'    => $validated['paid_amount'],
                'remaining_bill' => $remaining,
                'payment_status' => $validated['payment_status'],
                'quantity'       => $validated['quantity'],
                'deal_at'        => now()->toDateTimeString(),
                'notes'          => $validated['notes']
            ]);

            $leads->update([
                'status' => 'CLOSED_WON',
                'temperature' => 'HOT',
                'updated_by' => auth()->id(),
            ]);

            return $sales;
        });
    }
}
