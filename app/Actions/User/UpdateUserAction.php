<?php

namespace App\Actions\User;

use App\Models\User;
use App\Models\ProfileModel;

class UpdateUserAction
{
    public function execute(User $user, array $data, bool $isSuperAdmin): User
    {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }

        if ($isSuperAdmin) {
            $user->id_branch = $data['id_branch'];
        }

        $user->save();
        ProfileModel::where('id_user', $user->id)->update([
            'id_user' => $user->id
        ]);
        $user->syncRoles([
            $data['role']
        ]);

        return $user;
    }
}
