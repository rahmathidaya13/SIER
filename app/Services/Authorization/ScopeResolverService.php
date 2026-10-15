<?php

namespace App\Services\Authorization;

use App\Support\ScopeKey;

class ScopeResolverService
{
    public function filters(array $filters = [])
    {
        $user = auth()->user();
        $filters['scope'] = match (true) {
            $user->hasRole('developer') => ScopeKey::GLOBAL,
            $user->hasRole('super_admin') => ScopeKey::GLOBAL_SUPER_ADMIN,
            $user->hasRole('supervisor')  => ScopeKey::branch($user->id_branch),
            default                       =>  ScopeKey::user($user->id),
        };

        return $filters;
    }
}
