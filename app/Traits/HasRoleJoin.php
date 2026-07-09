<?php

namespace App\Traits;

use App\Models\User;

trait HasRoleJoin
{
    public function scopeJoinUserRole($query)
    {
        return $query
            ->leftJoin('users', 'users.id', '=', $this->getTable() . '.id_user')
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id');
    }
}
