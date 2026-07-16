<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    use HandlesAuthorization;
    public function before(User $user, $ability)
    {
        // Developer selalu lolos dari semua pengecekan di bawah
        if ($user->hasRole('developer')) {
            return true;
        }
        return null;
    }

    /**
     * Helper: Memeriksa apakah User berasal dari Kantor Pusat (Official Branch)
     */
    private function isFromHeadquarters(User $user): bool
    {
        // Pastikan user memiliki branch_id
        if (!$user->id_branch) {
            return false;
        }

        // Cek status cabang apakah bernilai 'official'
        // Memerlukan relasi 'branch' di model User
        return $user->branch && $user->branch->status === 'official';
    }

    /**
     * 2. LOGIKA CREATE (Hanya Orang Pusat)
     */
    public function create(User $user)
    {
        // A. Wajib memiliki permission create
        if (!$user->hasPermissionTo('product-create')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menambah atau membuat produk.');
        }

        // B. Wajib berasal dari Kantor Pusat
        return $this->isFromHeadquarters($user)
            ? Response::allow()
            : Response::deny('Akses ditolak: Hanya pegawai Kantor Pusat yang berhak menambah produk.');
    }


    /**
     * 3. LOGIKA EDIT (Hanya Orang Pusat)
     */
    public function update(User $user, ProductModel $product)
    {
        // A. Wajib memiliki permission edit
        if (!$user->hasPermissionTo('product-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah produk.');
        }

        // B. Wajib berasal dari Kantor Pusat
        return $this->isFromHeadquarters($user)
            ? Response::allow()
            : Response::deny('Akses ditolak: Hanya pegawai Kantor Pusat yang berhak mengubah produk.');
    }

    /**
     * 4. LOGIKA DELETE (Hanya Orang Pusat)
     */
    public function delete(User $user, ProductModel $product)
    {
        // A. Wajib memiliki permission delete
        if (!$user->hasPermissionTo('product-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus produk.');
        }

        // B. Wajib berasal dari Kantor Pusat
        return $this->isFromHeadquarters($user)
            ? Response::allow()
            : Response::deny('Akses ditolak: Hanya pegawai Kantor Pusat yang berhak menghapus produk.');
    }
}
