<?php

namespace App\Services;

use App\Models\LeadModel;
use Illuminate\Support\Facades\DB;

class LeadServices
{
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
