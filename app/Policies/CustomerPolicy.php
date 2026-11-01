<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CustomerModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    use HandlesAuthorization;
    public function before(User $user, $ability)
    {
        // Developer selalu lolos dari semua pengecekan di bawah
        if ($user->hasRole(['developer', 'super_admin'])) {
            return true;
        }
        return null;
    }

    /**
     * Izin melihat halaman daftar pelanggan (index).
     */
    public function viewAny(User $user): Response
    {
        return $user->can('customer-view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar pelanggan.');
    }

    /**
     * Izin melihat detail 1 pelanggan spesifik (show).
     */
    public function view(User $user, CustomerModel $customer): Response
    {
        if (!$user->can('customer-view')) {
            return Response::deny('Anda tidak memiliki izin untuk melihat data pelanggan.');
        }

        return $this->isSameBranch($user, $customer)
            ? Response::allow()
            : Response::deny('Akses ditolak: Anda hanya bisa melihat data pelanggan dari cabang Anda sendiri.');
    }

    /**
     * Izin menambah pelanggan baru (store).
     */
    public function create(User $user): Response
    {
        return $user->hasPermissionTo('customer-create')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki wewenang untuk membuat data pelanggan.');
    }

    /**
     * Izin mengubah data pelanggan (update).
     */
    public function update(User $user, CustomerModel $customer): Response
    {
        if (!$user->hasPermissionTo('customer-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah data pelanggan.');
        }

        return $this->authorizeModification($user, $customer, 'mengubah');
    }

    public function delete(User $user, CustomerModel $customer)
    {
        if (!$user->hasPermissionTo('customer-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus data pelanggan.');
        }

        return $this->isSameBranch($user, $customer)
            ? Response::allow()
            : Response::deny('Akses ditolak: Anda hanya bisa menghapus data pelanggan dari cabang Anda sendiri.');
    }
    private function isSameBranch(User $user, CustomerModel $customer): bool
    {
        if ($user->hasRole(['super_admin', 'developer', 'supervisor'])) {
            return true;
        }

        return $user->id_branch === $customer->created_of_branch;
    }
}
