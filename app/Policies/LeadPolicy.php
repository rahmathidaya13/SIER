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
     * God Mode: Developer selalu memiliki akses penuh
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('developer')) {
            return true;
        }
        return null;
    }

    /**
     * Helper: Memeriksa apakah User dan Lead berada di cabang yang sama.
     * Super Admin otomatis lolos pengecekan ini.
     */
    private function isSameBranch(User $user, LeadModel $lead): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->id_branch === $lead->id_branch;
    }

    /**
     * 1. Akses Halaman Index Leads (View Any)
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('lead-view');
    }

    /**
     * 2. Akses Detail Lead Tertentu (View)
     */
    public function view(User $user, LeadModel $lead)
    {
        if (!$user->hasPermissionTo('lead-view')) {
            return Response::deny('Anda tidak memiliki izin untuk melihat lead.');
        }

        return $this->isSameBranch($user, $lead)
            ? Response::allow()
            : Response::deny('Akses ditolak: Anda hanya bisa melihat lead dari cabang Anda sendiri.');
    }

    /**
     * 3. Akses Membuat Lead Baru (Create)
     */
    public function create(User $user)
    {
        // Pembuatan lead biasanya tidak butuh cek cabang,
        // karena saat disimpan otomatis akan masuk ke cabang user yang login

        if (!$user->hasPermissionTo('lead-create')) {
            return Response::deny('Anda tidak memiliki wewenang untuk membuat lead.');
        }

        return Response::allow();
    }

    /**
     * 4. Akses Mengubah Lead (Update)
     */
    public function update(User $user, LeadModel $lead)
    {
        if (!$user->hasPermissionTo('lead-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah lead.');
        }

        return $this->isSameBranch($user, $lead)
            ? Response::allow()
            : Response::deny('Akses ditolak: Anda hanya bisa mengubah lead dari cabang Anda sendiri.');
    }

    /**
     * 5. Akses Menghapus Lead (Delete)
     */
    public function delete(User $user, LeadModel $lead)
    {
        if (!$user->hasPermissionTo('lead-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus lead.');
        }

        return $this->isSameBranch($user, $lead)
            ? Response::allow()
            : Response::deny('Akses ditolak: Anda hanya bisa menghapus lead dari cabang Anda sendiri.');
    }
}
