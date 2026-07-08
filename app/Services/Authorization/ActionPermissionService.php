<?php

namespace App\Services\Authorization;

use App\Models\User;

class ActionPermissionService
{
    public function canCreate(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',
            'supervisor',
            'admin'
        ]);
    }

    public function canDelete(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',
            'supervisor'
        ]);
    }

    public function canApprove(User $user): bool
    {
        return $user->hasRole('supervisor')
            || $user->hasRole('super_admin');
    }

    public function canExport(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}
