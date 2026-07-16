<?php

namespace App\Traits;

use App\Models\User;

trait UserScope
{
    protected function scopeId(User $user): string|int
    {
        return match (true) {
            // Cache khusus Developer (Isi: Semua Data)
            $user->hasRole('developer')   => 'global',

            // Cache khusus Super Admin (Isi: Semua Data, minus Developer)
            $user->hasRole('super_admin') => 'global_super_admin',

            // Cache lokal untuk Supervisor/Sales (Isi: Hanya cabang mereka)
            default                       => 'branch_' . $user->id_branch,
        };
    }
}
