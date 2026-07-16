<?php

namespace App\Services\Authorization;

class ScopeResolverService
{
    public function filters(array $filters = [])
    {
        $user = auth()->user();
        $filters['scope'] = match (true) {
            $user->hasRole('developer') => 'global',
            $user->hasRole('super_admin') => 'global_super_admin',
            $user->hasRole('supervisor')  => 'branch_' . $user->id_branch,
            default                       => 'user_' . $user->id,
        };

        return $filters;
    }
}
