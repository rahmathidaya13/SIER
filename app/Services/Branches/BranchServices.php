<?php

namespace App\Services\Branches;

use App\Models\BranchModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchServices
{
    public function store(array $validated, $user): BranchModel
    {
        DB::beginTransaction();
        try {
            $branch = BranchModel::create([
                'name' => $validated['name'],
                'branch_code' => BranchModel::generateUniqueCode(),
                'created_by' => $user->id,
                'address' => $validated['address'],
                'is_active' => $validated['isActive'],
                'status' => $validated['status']
            ]);

            if (!empty($validated['phones'])) {
                $branchesMany = array_map(fn($phone) => array_merge($phone),    $validated['phones']);
                $branch->branchPhone()->createMany($branchesMany);
            }

            DB::commit();
            return $branch;
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal menyimpan data branch: " . $error);
            throw $error;
        }
    }

    public function update(BranchModel $branch, array $validated, $user): BranchModel
    {
        DB::beginTransaction();
        try {
            $branch->update([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'is_active' => $validated['isActive'],
                'status' => $validated['status']
            ]);

            if (isset($validated['phones']) && is_array($validated['phones'])) {
                $branch->branchPhone()->delete();
                $branchesMany = array_map(fn($phone) => array_merge($phone),    $validated['phones']);
                if (!empty($branchesMany)) {
                    $branch->branchPhone()->createMany($branchesMany);
                }
            }

            DB::commit();
            return $branch;
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal memperbarui data branch: " . $error);
            throw $error;
        }
    }
}
