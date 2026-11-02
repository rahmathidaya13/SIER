<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserBusinessRuleService
{
    public function ensureUniqueRolePerBranch(User $targetUser, string $role, string $branchId): void
    {
        // hanya supervisor yang dibatasi oleh cabang tidak boleh ada lebih dari 1
        if ($role !== 'supervisor') {
            return;
        }

        $exists = User::query()
            ->whereKeyNot($targetUser->id)
            ->where('id_branch', $branchId)
            ->where('is_active', true)
            ->role('supervisor')
            ->exists();


        if ($exists) {
            throw ValidationException::withMessages([
                'role' => 'Cabang ini sudah memiliki Supervisor aktif. Nonaktifkan atau ubah role Supervisor yang ada terlebih dahulu.',
            ]);
        }
    }
}
