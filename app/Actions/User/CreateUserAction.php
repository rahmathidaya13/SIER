<?php

namespace App\Actions\User;

use App\Models\User;
use App\Models\ProfileModel;

class CreateUserAction
{
    public function execute(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'id_branch' => $data['id_branch'],
            'email_verified_at' => now(),
        ]);

        ProfileModel::create([
            'id_user' => $user->id,
        ]);

        $user->assignRole($data['role']);

        return $user;
    }
}
