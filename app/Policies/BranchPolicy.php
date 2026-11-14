<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BranchModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
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
        return $user->can('branch-view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar cabang.');
    }

    public function view(User $user): Response
    {
        return $user->can('branch-view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat detail cabang.');
    }

    public function create(User $user): Response
    {
        return $user->can('branch-create')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki wewenang untuk membuat cabang.');
    }

    public function update(User $user, BranchModel $branch): Response
    {
        if (!$user->can('branch-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah cabang.');
        }

        return $this->authorizeModification($user, $branch, 'mengubah');
    }

    public function delete(User $user, BranchModel $branch): Response
    {
        if (!$user->can('branch-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus cabang.');
        }
        return $this->authorizeModification($user, $branch, 'menghapus');
    }

    private function authorizeModification(User $user,  BranchModel $branch, string $actionText): Response
    {
        $creator = $branch->creator;
        // Aturan untuk Supervisor (SPV):
        // Boleh ubah/hapus semua data KECUALI data buatan super_admin / developer
        if ($user->hasRole('supervisor')) {
            if ($creator && $creator->hasRole(['super_admin', 'developer'])) {
                return Response::deny("Akses ditolak:  Tidak dapat {$actionText} data yang dibuat oleh Akun Official");
            }

            return Response::allow();
        }

        // Aturan untuk Role di bawah SPV (Sales / Staff):
        // Dilarang ubah/hapus data buatan atasan, dan HANYA boleh ubah/hapus data buatannya sendiri
        if ($creator && $creator->hasRole(['super_admin', 'developer', 'supervisor'])) {
            return Response::deny("Akses ditolak: Anda tidak dapat {$actionText} data yang dibuat oleh atasan.");
        }

        if ((string) $branch->created_by !== (string) $user->id) {
            return Response::deny("Akses ditolak: Anda hanya dapat {$actionText} data yang Anda buat sendiri.");
        }
        return Response::allow();
    }
}
