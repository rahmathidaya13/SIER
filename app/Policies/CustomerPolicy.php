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
        return $user->can('customer-create')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki wewenang untuk membuat data pelanggan.');
    }

    /**
     * Izin mengubah data pelanggan (update).
     */
    public function update(User $user, CustomerModel $customer): Response
    {
        if (!$user->can('customer-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah data pelanggan.');
        }

        return $this->authorizeModification($user, $customer, 'mengubah');
    }

    /**
     * Izin menghapus data pelanggan (destroy).
     */
    public function delete(User $user, CustomerModel $customer): Response
    {
        if (!$user->can('customer-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus data pelanggan.');
        }

        return $this->authorizeModification($user, $customer, 'menghapus');
    }

    /**
     * Cek apakah user berada di cabang yang sama dengan data pelanggan.
     * (Berlaku untuk SPV maupun Sales karena Super Admin/Dev sudah lolos di before())
     */
    private function isSameBranch(User $user, CustomerModel $customer): bool
    {
        return !empty($user->id_branch)
            && (string) $user->id_branch === (string) $customer->created_of_branch;
    }

    /**
     * Validasi gabungan untuk Update & Delete:
     * 1. Wajib satu cabang
     * 2. Patuhi hierarki pembuat data (SPV vs Super Admin, Sales vs SPV/Sales lain)
     */
    private function authorizeModification(User $user, CustomerModel $customer, string $actionText): Response
    {
        // 1. Cek cabang terlebih dahulu (SPV & Sales wajib satu cabang)
        if (!$this->isSameBranch($user, $customer)) {
            return Response::deny("Akses ditolak: Anda hanya bisa {$actionText} data pelanggan dari cabang Anda sendiri.");
        }

        // Ambil user pembuat data (gunakan relasi creator yang sudah diberi withTrashed())
        $creator = $customer->creator;

        // 2. Aturan untuk Supervisor (SPV):
        // Boleh ubah/hapus semua data di cabangnya, KECUALI data buatan super_admin / developer
        if ($user->hasRole('supervisor')) {
            if ($creator && $creator->hasRole(['super_admin', 'developer'])) {
                return Response::deny("Akses ditolak:  Tidak dapat {$actionText} data yang dibuat oleh Akun Official");
            }

            return Response::allow();
        }

        // 3. Aturan untuk Role di bawah SPV (Sales / Staff):
        // Dilarang ubah/hapus data buatan atasan, dan HANYA boleh ubah/hapus data buatannya sendiri
        if ($creator && $creator->hasRole(['super_admin', 'developer', 'supervisor'])) {
            return Response::deny("Akses ditolak: Anda tidak dapat {$actionText} data yang dibuat oleh atasan.");
        }

        if ((string) $customer->created_by !== (string) $user->id) {
            return Response::deny("Akses ditolak: Anda hanya dapat {$actionText} data pelanggan yang Anda buat sendiri.");
        }

        return Response::allow();
    }
}
