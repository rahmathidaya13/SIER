<?php

namespace App\Policies;

use App\Models\SalesModel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SalesPolicy
{
    use HandlesAuthorization;

    /**
     * Developer & Super Admin bebas akses penuh ke seluruh cabang dan transaksi.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['developer', 'super_admin'])) {
            return true;
        }

        return null;
    }

    /**
     * 1. Akses Halaman Index Penjualan (viewAny)
     */
    public function viewAny(User $user): Response
    {
        return $user->can('closing-view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar rekap closing.');
    }

    /**
     * 2. Akses Detail Penjualan / Invoice (view)
     */
    public function view(User $user, SalesModel $sales): Response
    {
        if (!$user->can('closing-view')) {
            return Response::deny('Anda tidak memiliki izin untuk melihat data rekap closing.');
        }

        // Orang Pusat (official) atau satu cabang boleh melihat detail invoice
        if ($this->isFromHeadquarters($user) || $this->isSameBranch($user, $sales)) {
            return Response::allow();
        }

        return Response::deny('Akses ditolak: Anda hanya bisa melihat rekap closing dari cabang Anda sendiri.');
    }

    /**
     * 3. Akses Membuat Penjualan Baru (create)
     */
    public function create(User $user): Response
    {
        return $user->can('closing-create')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki wewenang untuk membuat rekap closing.');
    }

    /**
     * 4. Akses Mengubah Data Penjualan (update)
     */
    public function update(User $user, SalesModel $sales): Response
    {
        if (!$user->can('closing-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah data rekap closing.');
        }

        return $this->authorizeModification($user, $sales, 'mengubah');
    }

    /**
     * 5. Akses Menghapus Data Penjualan (delete)
     */
    public function delete(User $user, SalesModel $sales): Response
    {
        if (!$user->can('closing-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus data rekap closing.');
        }

        return $this->authorizeModification($user, $sales, 'menghapus');
    }

    /**
     * Cek apakah user berasal dari Kantor Pusat (official)
     */
    private function isFromHeadquarters(User $user): bool
    {
        return $user->branch && $user->branch->status === 'official';
    }

    /**
     * Cek kecocokan cabang antara User dan transaksi Sales
     */
    private function isSameBranch(User $user, SalesModel $sales): bool
    {
        return !empty($user->id_branch)
            && (string) $user->id_branch === (string) $sales->id_branch;
    }

    /**
     * Validasi Berlapis untuk Update & Delete:
     * 1. Cek Status Pembayaran (LUNAS / DP terkunci untuk non-Super Admin/Dev)
     * 2. Cek Kesamaan Cabang
     * 3. Cek Hierarki Pembuat Data (SPV vs Team/Sales)
     */
    private function authorizeModification(User $user, SalesModel $sales, string $actionText): Response
    {
        // 1. Proteksi Transaksional: Transaksi LUNAS / DP tidak boleh diubah/dihapus oleh level cabang
        if (in_array(strtoupper((string) $sales->payment_status), ['LUNAS', 'DP'], true)) {
            return Response::deny("Transaksi yang sudah LUNAS atau Dana Awal (DP) terkunci dan tidak dapat {$actionText} lagi.");
        }

        // 2. Wajib berasal dari cabang yang sama
        if (!$this->isSameBranch($user, $sales)) {
            return Response::deny("Akses ditolak: Anda hanya bisa {$actionText} rekap closing dari cabang Anda sendiri.");
        }

        // Ambil objek user pembuat data (pastikan relasi creator() di SalesModel memakai withTrashed())
        $creator = $sales->creator;

        // 3. Aturan untuk Supervisor (SPV):
        // Boleh mengubah/menghapus transaksi milik tim di cabangnya, KECUALI buatan Super Admin / Developer
        if ($user->hasRole('supervisor')) {
            if ($creator && $creator->hasAnyRole(['super_admin', 'developer'])) {
                return Response::deny("Akses ditolak: Anda tidak dapat {$actionText} rekap closing yang dibuat oleh Akun Official.");
            }

            return Response::allow();
        }

        // 4. Aturan untuk Anggota Tim (Sales / Admin Cabang):
        // Tidak boleh menyentuh data buatan atasan, dan HANYA boleh mengubah/menghapus buatannya sendiri
        if ($creator && $creator->hasAnyRole(['super_admin', 'developer', 'supervisor'])) {
            return Response::deny("Akses ditolak: Anda tidak dapat {$actionText} rekap closing yang dibuat oleh atasan.");
        }

        if ((string) $sales->created_by !== (string) $user->id) {
            return Response::deny("Akses ditolak: Anda hanya dapat {$actionText} data rekap closing yang Anda buat sendiri.");
        }

        return Response::allow();
    }
}
