<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ChannelModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChannelPolicy
{
    use HandlesAuthorization;
    /**
     * Developer & Super Admin bebas akses semua channel.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['developer', 'super_admin'])) {
            return true;
        }

        return null;
    }
    public function viewAny(User $user): Response
    {
        return $user->can('channel-view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat daftar sumber trafik (channel).');
    }

    public function view(User $user, ?ChannelModel $channel = null): Response
    {
        return $user->can('channel-view')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki izin untuk melihat detail sumber trafik (channel).');
    }


    public function create(User $user): Response
    {
        return $user->can('channel-create')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki wewenang untuk membuat sumber trafik (channel).');
    }

    public function update(User $user, ChannelModel $channel): Response
    {
        if (!$user->can('channel-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah sumber trafik (channel).');
        }

        return $this->authorizeModification($user, $channel, 'mengubah');
    }

    public function delete(User $user, ChannelModel $channel): Response
    {
        if (!$user->can('channel-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus sumber trafik (channel).');
        }

        return $this->authorizeModification($user, $channel, 'menghapus');
    }

    private function authorizeModification(User $user, ChannelModel $channel, string $actionText): Response
    {
        $creator = $channel->creator;

        // 1. Aturan untuk Supervisor (SPV):
        // Boleh ubah/hapus semua data KECUALI data buatan super_admin / developer / sistem
        if ($user->hasRole('supervisor')) {
            if (!$creator || $creator->hasAnyRole(['super_admin', 'developer'])) {
                return Response::deny("Akses ditolak: Tidak dapat {$actionText} sumber trafik yang dibuat oleh Akun Official.");
            }

            return Response::allow();
        }

        // 2. Aturan untuk Role di bawah SPV (Sales / Admin / Teknisi):
        // Dilarang ubah/hapus data buatan atasan, dan HANYA boleh ubah/hapus data buatannya sendiri
        if (!$creator || $creator->hasAnyRole(['super_admin', 'developer', 'supervisor'])) {
            return Response::deny("Akses ditolak: Anda tidak dapat {$actionText} sumber trafik yang dibuat oleh atasan.");
        }

        if ((string) $channel->created_by !== (string) $user->id) {
            return Response::deny("Akses ditolak: Anda hanya dapat {$actionText} sumber trafik yang Anda buat sendiri.");
        }

        return Response::allow();
    }
}
