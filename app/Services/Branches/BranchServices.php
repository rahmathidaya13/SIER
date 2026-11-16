<?php

namespace App\Services\Branches;

use App\Models\BranchModel;
use Illuminate\Support\Facades\DB;

class BranchServices
{
    public function store(array $validated, $user): BranchModel
    {
        return DB::transaction(function () use ($validated, $user) {
            $branch = BranchModel::create([
                'name' => $validated['name'],
                'branch_code' => BranchModel::generateUniqueCode(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'address' => $validated['address'],
                'is_active' => $validated['isActive'],
                'status' => $validated['status']
            ]);

            if (!empty($validated['phones'])) {
                $branchesMany = array_map(fn($phone) => array_merge($phone),    $validated['phones']);
                $branch->branchPhone()->createMany($branchesMany);
            }

            return $branch;
        });
    }

    public function update(BranchModel $branch, array $validated, $user): BranchModel
    {
        return DB::transaction(function () use ($branch, $validated, $user) {
            $branch->update([
                'name' => $validated['name'],
                'updated_by' => $user->id,
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

            return $branch;
        });
    }

    public function delete(BranchModel $branch)
    {
        return DB::transaction(function () use ($branch) {
            $branch->branchPhone()->delete();
            $branch->delete();
        });
    }
}
