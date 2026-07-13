<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserAuthorizationService
{
    public function ensureCanCreate(User $actor, ...$role): void
    {
        if (!$actor->hasAnyRole($role)) {
            throw ValidationException::withMessages([
                'unauthorized_action' => 'Anda tidak memiliki wewenang untuk menambah atau mengubah data!'
            ]);
        }
    }
    public function ensureCanUpdate(User $actor, User $target)
    {

        if ($actor->hasRole('super_admin')) {

            if ($actor->id === $target->id) {
                throw ValidationException::withMessages([
                    'unauthorized_action' => 'Super Admin tidak dapat mengubah akun sendiri.'
                ]);
            }

            return;
        }

        if ($actor->id_branch !== $target->id_branch) {

            throw ValidationException::withMessages([
                'unauthorized_action' => 'Akses ditolak: Pegawai ini bukan berada di cabang Anda.'
            ]);
        }

        if ($target->hasAnyRole(['super_admin', 'supervisor'])) {
            throw ValidationException::withMessages([
                'unauthorized_action' => 'Sistem mendeteksi tindakan ilegal: Anda tidak berhak mengubah data level Atasan.'
            ]);
        }
    }
    public function ensureCanDelete(User $actor, User $target): void
    {
        if ($actor->hasRole('super_admin') && $actor->id === $target->id) {
            throw ValidationException::withMessages([
                'unauthorized_action' => 'Super Admin tidak dapat menghapus akun sendiri.'
            ]);
        }


        // Super Admin boleh hapus selain dirinya
        if ($actor->hasRole('super_admin')) {
            return;
        }

        // Supervisor hanya cabangnya sendiri
        if ($actor->id_branch !== $target->id_branch) {
            throw ValidationException::withMessages([
                'unauthorized_action' => 'Pegawai ini bukan berada di cabang Anda.'
            ]);
        }

        // Supervisor tidak boleh hapus atasan
        if ($target->hasAnyRole(['super_admin', 'supervisor'])) {
            throw ValidationException::withMessages([
                'error' => 'Anda tidak dapat menghapus data atasan.'
            ]);
        }
    }
    public function allowedRoles(User $user): array
    {
        return $user->hasRole('super_admin')
            ? ['supervisor', 'admin', 'sales', 'teknisi']
            : ['admin', 'sales', 'teknisi'];
    }
}
