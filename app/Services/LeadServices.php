<?php

namespace App\Services;

use App\Models\LeadModel;
use App\Traits\ImmutableFieldValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadServices
{
    use ImmutableFieldValidator;
    public function store(array $data, $user): LeadModel
    {
        return DB::transaction(function () use ($data, $user) {
            $leads = LeadModel::create([
                'id_user' => $user->id,
                'id_branch' => $user->id_branch,
                'id_channel' => $data['id_channel'],
                'id_product' => $data['id_product'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'status' => $data['status'],
                'temperature' => $data['temperature'],
                'follow_up_date' => $data['follow_up_date'],
                'notes' => $data['notes'],
            ]);
            if (!empty($data['new_activity'])) {
                $leads->activity()->create([
                    'id_user'     => $user->id,
                    'id_lead'     => $leads->id_lead,
                    'description' => $data['new_activity']
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
            $this->validate($leads, $data, [
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
}
