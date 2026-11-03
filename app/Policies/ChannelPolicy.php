<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ChannelModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChannelPolicy
{
    use HandlesAuthorization;
    public function before(User $user, $ability)
    {
        if ($user->hasRole(['developer', 'super_admin'])) {
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
        return $user->can('channel-edit')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki wewenang untuk mengubah sumber trafik (channel).');
    }

    public function delete(User $user, ChannelModel $channel): Response
    {
        return $user->can('channel-delete')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki wewenang untuk menghapus sumber trafik (channel).');
    }
}
