<?php

namespace App\Services\Authorization;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DataScopeService
{
    public function apply(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole('super_admin')
            => $query,

            $user->hasRole('supervisor')
            => $query->where(
                $query->getModel()->getTable() . '.id_branch',
                $user->id_branch
            ),

            default
            => $query->where(
                $query->getModel()->getTable() . '.id_user',
                $user->id
            ),
        };
    }
}
