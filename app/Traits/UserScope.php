<?php

namespace App\Traits;

use App\Models\User;

trait UserScope
{
    protected function scopeId(User $user): string|int
    {
        return $user->hasRole('super_admin')
            ? 'global'
            : $user->id_branch;
    }
}
