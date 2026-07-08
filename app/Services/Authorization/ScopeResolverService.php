<?php

namespace App\Services\Authorization;

use App\Models\User;

class ScopeResolverService
{
    public function filters(array $filters = [])
    {
        $user = auth()->user();
        $filters['scope'] = match (true) {
            $user->hasRole('super_admin') => 'global',
            $user->hasRole('supervisor')  => 'id_branch_' . $user->id_branch,
            default                       => 'id_user_' . $user->id,
        };

        return $filters;
    }
}
