<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Developer & Super Admin otomatis lolos semua pengecekan.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['developer', 'super_admin'])) {
            return true;
        }

        return null;
    }

    /**
     * 1. Akses Halaman Tabel Produk (index)
     */
    public function viewAny(User $user): Response
    {
        return $user->can('product-view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar produk.');
    }
    /**
     * 2. Akses Detail Produk (show)
     */
    public function view(User $user, ?ProductModel $product = null): Response
    {
        return $user->can('product-view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat detail produk.');
    }

    /**
     * 3. Akses Tambah Produk (store)
     * Bebas cabang, selama diberikan permission 'product-create'
     */
    public function create(User $user): Response
    {
        return $user->can('product-create')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki wewenang untuk menambah atau membuat produk.');
    }
    /**
     * 4. Akses Ubah Produk (update)
     * Bebas cabang, selama diberikan permission 'product-edit'
     */
    public function update(User $user, ProductModel $product): Response
    {
        if (!$user->can('product-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah produk.');
        }

        return $this->authorizeModification($user, $product, 'mengubah');
    }

    /**
     * 5. Akses Hapus Produk (destroy)
     * Bebas cabang, selama diberikan permission 'product-delete'
     */
    public function delete(User $user, ProductModel $product): Response
    {
        if (!$user->can('product-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus produk.');
        }
        return $this->authorizeModification($user, $product, 'menghapus');
    }

    private function authorizeModification(User $user, ProductModel $product, string $actionText): Response
    {
        $creator = $product->creator;

        // Supervisor boleh ubah/hapus produk, kecuali yang dibuat oleh Super Admin / Developer
        if ($user->hasRole('supervisor')) {
            if ($creator && $creator->hasAnyRole(['super_admin', 'developer'])) {
                return Response::deny("Akses ditolak: Supervisor tidak dapat {$actionText} produk yang dibuat oleh Super Admin / Developer.");
            }

            return Response::allow();
        }

        // Role di bawah SPV tidak boleh ubah/hapus produk buatan atasan
        if ($creator && $creator->hasAnyRole(['super_admin', 'developer', 'supervisor'])) {
            return Response::deny("Akses ditolak: Anda tidak dapat {$actionText} produk yang dibuat oleh atasan.");
        }

        return Response::allow();
    }
}
