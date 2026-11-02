<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Developer bebas melakukan aksi viewAny, view, dan create.
     * Untuk update, toggleStatus, dan delete tetap masuk ke fungsi masing-masing
     * agar ada validasi keamanan terhadap akun sendiri (self-protection).
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('developer') && !in_array($ability, ['delete', 'toggleStatus'])) {
            return true;
        }

        return null;
    }

    /**
     * Izin melihat daftar pegawai (index).
     */
    public function viewAny(User $user): Response
    {
        return $user->hasAnyRole(['developer', 'super_admin'])
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar pegawai.');
    }

    /**
     * Izin menambah pegawai baru (store).
     */
    public function create(User $user): Response
    {
        return $user->hasAnyRole(['developer', 'super_admin'])
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk menambah pegawai.');
    }

    /**
     * Izin mengubah data pegawai (update).
     */
    public function update(User $currentUser, User $targetUser): Response
    {
        // 1. Developer bebas mengubah siapa saja
        if ($currentUser->hasRole('developer')) {
            return Response::allow();
        }

        // 2. Cegah Super Admin & Supervisor mengubah akunnya sendiri lewat menu manajemen pegawai
        // (Ubah profil sendiri sebaiknya lewat menu Profile Settings)
        if ((string) $currentUser->id === (string) $targetUser->id) {
            return Response::deny('Akses ditolak: Anda tidak dapat mengubah akun Anda sendiri dari menu ini.');
        }

        // 3. Aturan Super Admin: tidak boleh mengubah akun Developer atau sesama Super Admin
        if ($currentUser->hasRole('super_admin')) {
            if ($targetUser->hasAnyRole(['developer', 'super_admin'])) {
                return Response::deny('Akses ditolak: Super Admin tidak dapat mengubah akun Developer atau Super Admin lainnya.');
            }

            return Response::allow();
        }

        // 4. Aturan Supervisor: wajib satu cabang & hanya boleh mengubah level bawahan (Sales/Staff)
        // if ($currentUser->hasRole('supervisor')) {
        //     if ((string) $currentUser->id_branch !== (string) $targetUser->id_branch) {
        //         return Response::deny('Akses ditolak: Pegawai ini tidak berada di cabang Anda.');
        //     }

        //     if ($targetUser->hasAnyRole(['developer', 'super_admin', 'supervisor'])) {
        //         return Response::deny('Akses ditolak: Anda tidak berhak mengubah data pegawai selevel atau di atas Anda.');
        //     }

        //     return Response::allow();
        // }

        return Response::deny('Akses ditolak: Anda tidak memiliki wewenang mengubah akun ini.');
    }

    /**
     * Izin menghapus akun pegawai (destroy).
     * HANYA boleh dilakukan oleh Developer.
     */
    public function delete(User $currentUser, User $targetUser): Response
    {
        if (!$currentUser->hasRole('developer')) {
            return Response::deny('Akses ditolak: Hanya Developer yang dapat menghapus akun pegawai. Gunakan fitur Nonaktifkan Akun.');
        }

        if ((string) $currentUser->id === (string) $targetUser->id) {
            return Response::deny('Akses ditolak: Developer tidak dapat menghapus akunnya sendiri.');
        }

        return Response::allow();
    }

    /**
     * Izin menonaktifkan / mengaktifkan kembali akun pegawai (is_active).
     * Boleh dilakukan oleh Developer & Super Admin (atau tambahkan Supervisor jika diizinkan).
     */
    public function toggleStatus(User $currentUser, User $targetUser): Response
    {
        // 1. Hanya developer & super_admin yang punya akses
        if (!$currentUser->hasRole(['developer', 'super_admin'])) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah status akun pegawai.');
        }

        // 2. Cegah menonaktifkan akun sendiri yang sedang dipakai login
        if ((string) $currentUser->id === (string) $targetUser->id) {
            return Response::deny('Akses ditolak: Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        // 3. Super Admin DILARANG menonaktifkan akun Developer atau sesama Super Admin
        if ($currentUser->hasRole('super_admin') && $targetUser->hasRole(['developer', 'super_admin'])) {
            return Response::deny('Akses ditolak: Super Admin tidak dapat menonaktifkan akun Developer atau Super Admin lainnya.');
        }

        return Response::allow();
    }
}
