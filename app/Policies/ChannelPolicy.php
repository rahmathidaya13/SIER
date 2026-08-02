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

    public function view(User $user)
    {
        return $user->hasPermissionTo('channel-view');
    }

    public function create(User $user)
    {
        if (!$user->hasPermissionTo('channel-create')) {
            return Response::deny('Anda tidak memiliki wewenang untuk membuat sumber trafik (channel).');
        }
        return Response::allow();
    }

    public function update(User $user, ChannelModel $channel)
    {
        if (!$user->hasPermissionTo('channel-edit')) {
            return Response::deny('Anda tidak memiliki wewenang untuk mengubah sumber trafik (channel).');
        }

        return Response::allow();
    }

    public function delete(User $user, ChannelModel $channel)
    {
        if (!$user->hasPermissionTo('channel-delete')) {
            return Response::deny('Anda tidak memiliki wewenang untuk menghapus sumber trafik (channel).');
        }

        return Response::allow();
    }
}
