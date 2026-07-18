<?php

namespace App\Policies;

use App\Models\SalesModel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SalesPolicy
{
    use HandlesAuthorization;
    public function before(User $user, $ability)
    {
        if ($user->hasRole('developer')) {
            return true;
        }
        return null;
    }
    /**
     * Helper: Memeriksa apakah User dan Penjualan berada di cabang yang sama.
     */
    private function isSameBranch(User $user, SalesModel $sale): bool
    {
        // Super Admin atau Orang Pusat boleh melihat penjualan dari semua cabang
        if ($user->hasRole('super_admin') || ($user->branch && $user->branch->status === 'official')) {
            return true;
        }

        return $user->id_branch === $sale->id_branch;
    }
    /**
     * 1. Akses Halaman Index Penjualan
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('closing-view');
    }

    /**
     * 2. Akses Detail Penjualan (Invoice/Struk)
     */
    public function view(User $user, SalesModel $sale)
    {
        if (!$user->hasPermissionTo('closing-view')) {
            return Response::deny('Anda tidak memiliki izin untuk melihat data rekap closing.');
        }

        return $this->isSameBranch($user, $sale)
            ? Response::allow()
            : Response::deny('Akses ditolak: Anda hanya bisa melihat rekap closing dari cabang Anda sendiri.');
    }

    /**
     * 3. Akses Membuat Penjualan Baru
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('closing-create');
    }

    /**
     * 4. Akses Mengubah Data Penjualan
     */
    public function update(User $user, SalesModel $sales)
    {
        if (!$user->hasPermissionTo('closing-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah data rekap closing.');
        }

        // PROTEKSI TRANSAKSIONAL: Mencegah edit jika transaksi sudah lunas/selesai (Opsional)
        // Pengecualian biasanya diberikan kepada role super_admin atau auditor
        if (in_array($sales->payment_status, ['LUNAS', 'DP']) && !$user->hasRole('super_admin')) {
            return Response::deny('Transaksi yang sudah lunas/dp tidak dapat diubah lagi.');
        }

        return $this->isSameBranch($user, $sales)
            ? Response::allow()
            : Response::deny('Akses ditolak: Anda hanya bisa mengubah rekap closing dari cabang Anda sendiri.');
    }

    /**
     * 5. Akses Menghapus Data Penjualan
     */
    public function delete(User $user, SalesModel $sales)
    {
        if (!$user->hasPermissionTo('closing-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus data rekap closing.');
        }

        // PROTEKSI TRANSAKSIONAL: Mencegah hapus data keuangan
        if (in_array($sales->payment_status, ['LUNAS', 'DP']) && !$user->hasRole('super_admin')) {
            return Response::deny('Transaksi yang sudah lunas/dp mutlak tidak boleh dihapus.');
        }

        return $this->isSameBranch($user, $sales)
            ? Response::allow()
            : Response::deny('Akses ditolak: Anda hanya bisa menghapus rekap closing dari cabang Anda sendiri.');
    }
}
