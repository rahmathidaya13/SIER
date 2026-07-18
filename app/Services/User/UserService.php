<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\ProfileModel;

class UserService
{
    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'id_branch' => $data['id_branch'],
            'email_verified_at' => now(),
            'is_active' => $data['is_active'],
        ]);

        ProfileModel::create([
            'id_user' => $user->id,
        ]);

        if (request()->filled('role')) {
            $user->assignRole($data['role']);
        }
        if (request()->has('permissions')) {
            $user->syncPermissions($data['permissions']);
        }

        return $user;
    }
    public function update(User $user, array $data, bool $canChangeBranch): User
    {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
        ]);

        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }

        if ($canChangeBranch) {
            $user->id_branch = $data['id_branch'];
        }

        $user->save();

        ProfileModel::updateOrCreate([
            'id_user' => $user->id
        ], []);

        if (request()->filled('role')) {
            $user->syncRoles([
                $data['role']
            ]);
        }

        if (request()->has('permissions')) {
            $user->syncPermissions($data['permissions']);
        }

        return $user;
    }
}
