<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LeadModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeadPolicy
{
    use HandlesAuthorization;

    /**
     * Developer & Super Admin bebas akses semua cabang.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['developer', 'super_admin'])) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): Response
    {
        return $user->can('lead-view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar lead.');
    }

    public function view(User $user, LeadModel $lead): Response
    {
        if (!$user->can('lead-view')) {
            return Response::deny('Anda tidak memiliki izin untuk melihat detail lead.');
        }

        return $this->isSameBranch($user, $lead)
            ? Response::allow()
            : Response::deny('Akses ditolak: Anda hanya bisa melihat lead dari cabang Anda sendiri.');
    }

    public function create(User $user): Response
    {
        return $user->can('lead-create')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki wewenang untuk membuat lead.');
    }

    public function update(User $user, LeadModel $lead): Response
    {
        if (!$user->can('lead-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah lead.');
        }

        return $this->authorizeModification($user, $lead, 'mengubah');
    }

    public function delete(User $user, LeadModel $lead): Response
    {
        if (!$user->can('lead-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus lead.');
        }

        return $this->authorizeModification($user, $lead, 'menghapus');
    }

    /**
     * Cek kecocokan cabang (Berlaku untuk SPV maupun Sales).
     */
    private function isSameBranch(User $user, LeadModel $lead): bool
    {
        return !empty($user->id_branch)
            && (string) $user->id_branch === (string) $lead->id_branch;
    }

    /**
     * Validasi cabang & hierarki pembuat Lead (Sama seperti isOwner di Vue).
     */
    private function authorizeModification(User $user, LeadModel $lead, string $actionText): Response
    {
        if (!$this->isSameBranch($user, $lead)) {
            return Response::deny("Akses ditolak: Anda hanya bisa {$actionText} lead dari cabang Anda sendiri.");
        }

        $creator = $lead->creator;

        // Supervisor boleh ubah/hapus lead di cabangnya, kecuali buatan Super Admin / Developer
        if ($user->hasRole('supervisor')) {
            if ($creator && $creator->hasAnyRole(['super_admin', 'developer'])) {
                return Response::deny("Akses ditolak: Tidak dapat {$actionText} lead yang dibuat oleh Akun Official");
            }

            return Response::allow();
        }

        // Role di bawah SPV (Sales/Admin) hanya boleh ubah/hapus miliknya sendiri
        if ($creator && $creator->hasAnyRole(['super_admin', 'developer', 'supervisor'])) {
            return Response::deny("Akses ditolak: Anda tidak dapat {$actionText} lead yang dibuat oleh atasan.");
        }

        if ((string) $lead->created_by !== (string) $user->id) {
            return Response::deny("Akses ditolak: Anda hanya dapat {$actionText} lead yang Anda buat sendiri.");
        }

        return Response::allow();
    }
}
