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
        // 🛡️ 1. HAK MUTLAK DEVELOPER
        if ($actor->hasRole('developer')) {
            return; // Lolos tanpa syarat
        }

        // 🛡️ 2. ATURAN SUPER ADMIN
        if ($actor->hasRole('super_admin')) {
            if ($actor->id === $target->id) {
                throw ValidationException::withMessages([
                    'unauthorized_action' => 'Super Admin tidak dapat mengubah akun sendiri.'
                ]);
            }

            // 🔥 CEGAH KUDETA: Super Admin dilarang menyentuh Developer!
            if ($target->hasRole('developer')) {
                throw ValidationException::withMessages([
                    'unauthorized_action' => 'Akses ditolak: Anda tidak memiliki wewenang mengubah akun Developer.'
                ]);
            }

            return;
        }

        // 🛡️ 3. ATURAN SUPERVISOR & BAWAHAN (Terkunci di Cabang)
        if ($actor->id_branch !== $target->id_branch) {

            throw ValidationException::withMessages([
                'unauthorized_action' => 'Akses ditolak: Pegawai ini bukan berada di cabang Anda.'
            ]);
        }

        // Bawahan dilarang mengedit Atasan (Sekarang Developer juga dimasukkan ke daftar lindungan)
        if ($target->hasAnyRole(['developer', 'super_admin'])) {
            throw ValidationException::withMessages([
                'unauthorized_action' => 'Sistem mendeteksi tindakan ilegal: Anda tidak berhak mengubah data level Atasan.'
            ]);
        }
    }
    public function ensureCanDelete(User $actor, User $target): void
    {
        // 🛡️ 1. HAK MUTLAK DEVELOPER
        if ($actor->hasRole('developer')) {
            if ($actor->id === $target->id) {
                throw ValidationException::withMessages([
                    'unauthorized_action' => 'Developer tidak dapat menghapus akun sendiri!'
                ]);
            }
            return; // Lolos untuk menghapus user lain siapa pun itu
        }

        // 🛡️ 2. ATURAN SUPER ADMIN
        if ($actor->hasRole('super_admin')) {
            if ($actor->id === $target->id) {
                throw ValidationException::withMessages([
                    'unauthorized_action' => 'Super Admin tidak dapat menghapus akun sendiri.'
                ]);
            }

            // 🔥 CEGAH KUDETA: Blokir Super Admin jika mencoba menghapus Developer
            if ($target->hasRole('developer')) {
                throw ValidationException::withMessages([
                    'unauthorized_action' => 'Akses ditolak: Anda tidak memiliki wewenang untuk menghapus akun Developer.'
                ]);
            }

            return; // Lolos untuk menghapus level di bawahnya lintas cabang
        }



        // 🛡️ 3. ATURAN SUPERVISOR & BAWAHAN (Terkunci di Cabang)
        if ($actor->id_branch !== $target->id_branch) {
            throw ValidationException::withMessages([
                'unauthorized_action' => 'Pegawai ini bukan berada di cabang Anda.'
            ]);
        }

        // Supervisor dilarang menghapus sesama Supervisor atau tingkatan di atasnya (Super Admin/Developer)
        if ($target->hasAnyRole(['developer', 'super_admin', 'supervisor'])) {
            throw ValidationException::withMessages([
                'unauthorized_action' => 'Sistem mendeteksi tindakan ilegal: Anda tidak berhak menghapus data level Atasan.'
            ]);
        }
    }
    public function allowedRoles(User $user): array|null|string|bool
    {
        $allowedRoles = match (true) {
            $user->hasRole('developer')   => ['super_admin', 'supervisor', 'admin', 'sales', 'teknisi'],
            $user->hasRole('super_admin') => ['supervisor', 'admin', 'sales', 'teknisi'],
            $user->hasRole('supervisor')  => ['admin', 'sales', 'teknisi'],
            default                                 => []
        };
        return $allowedRoles;
    }
}
