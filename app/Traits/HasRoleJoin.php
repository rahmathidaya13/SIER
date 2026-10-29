<?php

namespace App\Traits;

use App\Models\User;

trait HasRoleJoin
{
    public function scopeJoinUserRole($query)
    {
        return $query
            ->leftJoin('users as creator', 'creator.id', '=', $this->getTable() . '.created_by')
            ->leftJoin('users as updater', 'updater.id', '=', $this->getTable() . '.updated_by')
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'creator.id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id');
    }
}
