<?php

namespace App\Services\Authorization\BranchAccessService;

use App\Models\User;

class BranchAccessService
{
    public function canAccess(User $user, ?string $branchId): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->id_branch === $branchId;
    }
}
