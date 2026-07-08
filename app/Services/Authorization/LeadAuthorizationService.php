<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Models\LeadModel;
use Illuminate\Validation\ValidationException;

class LeadAuthorizationService
{
    public function ensureCanDelete(User $user, LeadModel $lead): void
    {
        if (!app(ActionPermissionService::class)->canDelete($user)) {
            throw ValidationException::withMessages([
                'error' => 'Akses ditolak: Anda tidak memiliki akses menghapus data.'
            ]);
        }

        if (
            !$user->hasAnyRole(['super_admin', 'supervisor']) &&
            $lead->id_branch !== $user->id_branch
        ) {
            throw ValidationException::withMessages([
                'error' => 'Lead ini berasal dari cabang lain.'
            ]);
        }

        if ($lead->status === 'CLOSED_WON') {
            throw ValidationException::withMessages([
                'error' => 'Lead yang sudah closing tidak dapat dihapus.'
            ]);
        }
    }
}
