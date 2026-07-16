<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['developer', 'super_admin', 'supervisor']);
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['developer', 'super_admin', 'supervisor'])
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk menambah pegawai.');
    }
    public function update(User $user, User $target)
    {
        // 1. Aturan Developer
        if ($user->hasRole('developer')) {
            return Response::allow();
        }

        // 2. Aturan Super Admin
        if ($user->hasRole('super_admin')) {
            if ($user->id === $target->id) {
                return Response::deny('Super Admin tidak dapat mengubah akun sendiri.');
            }
            if ($target->hasRole('developer')) {
                return Response::deny('Akses ditolak: Anda tidak memiliki wewenang mengubah akun Developer.');
            }
            return Response::allow();
        }

        // 3. Aturan Supervisor
        if ($user->hasRole('supervisor')) {
            if ($user->id_branch !== $target->id_branch) {
                return Response::deny('Akses ditolak: Pegawai ini tidak berada di cabang Anda.');
            }
            if ($target->hasAnyRole(['developer', 'super_admin', 'supervisor'])) {
                return Response::deny('Sistem mendeteksi tindakan ilegal: Anda tidak berhak mengubah data level Atasan.');
            }
            return Response::allow();
        }

        return Response::deny('Akses ditolak. Anda tidak memiliki wewenang mengubah akun ini.');
    }
    public function deleted(User $user, User $target)
    {
        // 1. Aturan Developer
        if ($user->hasRole('developer')) {
            if ($user->id === $target->id) {
                return Response::deny('Developer tidak dapat menghapus akunnya sendiri!');
            }
            return Response::allow();
        }

        // 2. Aturan Super Admin
        if ($user->hasRole('super_admin')) {
            if ($user->id === $target->id) {
                return Response::deny('Super Admin tidak dapat menghapus akun sendiri.');
            }
            if ($target->hasRole('developer')) {
                return Response::deny('Akses ditolak: Anda tidak memiliki wewenang untuk menghapus akun Developer.');
            }
            return Response::allow();
        }

        // 3. Aturan Supervisor
        if ($user->hasRole('supervisor')) {
            if ($user->id_branch !== $target->id_branch) {
                return Response::deny('Akses ditolak: Pegawai ini tidak berada di cabang Anda.');
            }
            if ($target->hasAnyRole(['developer', 'super_admin'])) {
                return Response::deny('Sistem mendeteksi tindakan ilegal: Anda tidak berhak menghapus data level Atasan.');
            }
        }


        return Response::deny('Akses ditolak: Anda tidak memiliki wewenang untuk menghapus akun ini.');
    }
}
